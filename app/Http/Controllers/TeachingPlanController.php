<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\LearningObjective;
use App\Models\LessonPlan;
use App\Models\Material;
use App\Models\Subject;
use App\Models\TeachingPlan;
use App\Services\TeachingPlanExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeachingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $selectedYear = AcademicYear::selectedFor($user);
        $selectedYearId = $selectedYear?->id;
        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $selectedYearId)
            || $user->hasStructuralRole('waka_kurikulum', $selectedYearId);

        $plans = TeachingPlan::query()
            ->forAcademicYear($selectedYearId)
            ->when(! $isLeadership, fn ($q) => $q->where('teacher_id', $user->id))
            ->with(['subject', 'teacher', 'academicYear'])
            ->withCount(['learningObjectives', 'lessonPlans'])
            ->orderBy('grade_level')
            ->orderBy('title')
            ->get();

        $teachingCourses = $user->teachingCourses()
            ->forAcademicYear($selectedYearId)
            ->with(['subject', 'classroom'])
            ->get();

        $subjectIds = $teachingCourses->pluck('subject_id')->filter()->unique()->all();

        $sharedPlans = ! $isLeadership && $subjectIds !== []
            ? TeachingPlan::query()
                ->where('is_shared', true)
                ->where('teacher_id', '!=', $user->id)
                ->whereIn('subject_id', $subjectIds)
                ->with(['subject', 'teacher', 'academicYear'])
                ->withCount(['learningObjectives', 'lessonPlans'])
                ->orderBy('grade_level')
                ->orderBy('title')
                ->get()
            : collect();

        $subjectGradeOptions = $teachingCourses
            ->filter(fn ($c) => $c->subject && $c->classroom)
            ->map(fn ($c) => [
                'subject_id' => $c->subject_id,
                'subject_name' => $c->subject->name,
                'grade_level' => Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name),
            ])
            ->unique(fn ($item) => $item['subject_id'].'-'.$item['grade_level'])
            ->sortBy([['subject_name', 'asc'], ['grade_level', 'asc']])
            ->values();

        if ($subjectGradeOptions->isEmpty() && $user->isAdmin()) {
            $subjectGradeOptions = Subject::orderBy('name')->get()->map(fn ($s) => [
                'subject_id' => $s->id,
                'subject_name' => $s->name,
                'grade_level' => 10,
            ]);
        }

        $planGradeGroups = $plans
            ->groupBy(fn (TeachingPlan $p): string => (string) ($p->grade_level ?? 'other'))
            ->map(function ($byGrade): array {
                $first = $byGrade->first();
                $subjects = $byGrade->groupBy(fn (TeachingPlan $p): string => (string) ($p->subject_id ?? 0))
                    ->map(function ($bySubject): array {
                        /** @var TeachingPlan $sample */
                        $sample = $bySubject->first();

                        return [
                            'subject_id' => $sample->subject_id ?? 0,
                            'subject_name' => $sample->subject?->name ?? 'Umum / Tanpa Mapel',
                            'plans' => $bySubject->values(),
                        ];
                    })
                    ->sortBy('subject_name')
                    ->values();

                return [
                    'grade_level' => $first->grade_level,
                    'subjects' => $subjects,
                    'total_plans' => $byGrade->count(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        return view('teaching-plans.index', [
            'plans' => $plans,
            'planGradeGroups' => $planGradeGroups,
            'sharedPlans' => $sharedPlans,
            'activeYear' => $selectedYear,
            'subjectGradeOptions' => $subjectGradeOptions,
            'isLeadership' => $isLeadership,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'subject_grade' => ['required', 'string'],
            'phase' => ['nullable', 'string', 'max:30'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        [$subjectId, $gradeLevel] = array_map('intval', explode(':', $validated['subject_grade']) + [0, 10]);
        $subject = Subject::findOrFail($subjectId);
        $selectedYear = AcademicYear::selectedFor($user) ?? AcademicYear::active();
        abort_unless($selectedYear !== null, 422, 'Tahun ajaran aktif belum tersedia.');

        $plan = TeachingPlan::firstOrCreate(
            [
                'academic_year_id' => $selectedYear->id,
                'subject_id' => $subject->id,
                'grade_level' => $gradeLevel,
                'teacher_id' => $user->id,
            ],
            [
                'phase' => ($validated['phase'] ?? null) ?: ($gradeLevel === 10 ? 'Fase E' : 'Fase F'),
                'title' => ($validated['title'] ?? null) ?: "Perangkat Ajar {$subject->name} Kelas {$gradeLevel}",
                'kktp_type' => 'interval',
                'kktp_min_score' => 75,
            ]
        );

        return redirect()->route('teaching-plans.show', $plan)
            ->with('status', 'Master Perangkat Ajar siap dikelola.');
    }

    public function show(Request $request, TeachingPlan $teachingPlan): View
    {
        $this->authorizeAccess($request, $teachingPlan);

        $teachingPlan->load([
            'academicYear',
            'subject',
            'teacher',
            'learningObjectives',
            'lessonPlans.learningObjective',
        ]);

        $parallelCourses = Course::query()
            ->where('academic_year_id', $teachingPlan->academic_year_id)
            ->where('subject_id', $teachingPlan->subject_id)
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->whereHas('teachers', fn ($tq) => $tq->where('users.id', $teachingPlan->teacher_id)))
            ->with(['classroom', 'subject'])
            ->orderBy('title')
            ->get()
            ->filter(fn (Course $course) => $course->classroom && Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name) === (int) $teachingPlan->grade_level)
            ->values();

        $copyablePlans = ($request->user()->isAdmin() || $teachingPlan->teacher_id === $request->user()->id)
            ? TeachingPlan::query()
                ->where('id', '!=', $teachingPlan->id)
                ->where('subject_id', $teachingPlan->subject_id)
                ->where('grade_level', $teachingPlan->grade_level)
                ->where(function ($q) use ($request): void {
                    $q->where('teacher_id', $request->user()->id)
                        ->orWhere('is_shared', true);
                })
                ->with(['teacher', 'academicYear'])
                ->withCount(['learningObjectives', 'lessonPlans'])
                ->latest('id')
                ->get()
            : collect();

        return view('teaching-plans.show', [
            'plan' => $teachingPlan,
            'parallelCourses' => $parallelCourses,
            'copyablePlans' => $copyablePlans,
            'totalAllocatedJp' => $teachingPlan->learningObjectives->sum('jp_allocation'),
        ]);
    }

    public function update(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'phase' => ['nullable', 'string', 'max:30'],
            'capaian_pembelajaran' => ['nullable', 'string'],
            'effective_weeks' => ['nullable', 'integer', 'min:1', 'max:60'],
            'total_jp' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'kktp_type' => ['required', 'in:interval,deskripsi,rubrik'],
            'kktp_min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'kktp_notes' => ['nullable', 'string'],
        ]);

        $teachingPlan->update($validated);

        return back()->with('status', 'CP, Prota, dan KKTP berhasil disimpan.');
    }

    public function storeObjective(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'jp_allocation' => ['required', 'integer', 'min:1', 'max:200'],
            'target_month' => ['nullable', 'string', 'max:60'],
        ]);

        $nextOrder = ((int) $teachingPlan->learningObjectives()->max('sort_order')) + 1;

        $teachingPlan->learningObjectives()->create([
            ...$validated,
            'sort_order' => $nextOrder,
        ]);

        return back()->with('status', 'Tujuan Pembelajaran (ATP) berhasil ditambahkan.');
    }

    public function updateObjective(Request $request, TeachingPlan $teachingPlan, LearningObjective $objective): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);
        abort_unless($objective->teaching_plan_id === $teachingPlan->id, 404);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'jp_allocation' => ['required', 'integer', 'min:1', 'max:200'],
            'target_month' => ['nullable', 'string', 'max:60'],
        ]);

        $objective->update($validated);

        return back()->with('status', 'Tujuan Pembelajaran (ATP) berhasil diperbarui.');
    }

    public function destroyObjective(Request $request, TeachingPlan $teachingPlan, LearningObjective $objective): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);
        abort_unless($objective->teaching_plan_id === $teachingPlan->id, 404);

        $objective->delete();

        return back()->with('status', 'Tujuan Pembelajaran berhasil dihapus.');
    }

    public function storeLessonPlan(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'learning_objective_id' => ['nullable', 'integer', 'exists:learning_objectives,id'],
            'learning_goals' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'assessment_plan' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
            'external_url' => ['nullable', 'url', 'max:500'],
        ]);

        $path = $request->hasFile('attachment')
            ? $request->file('attachment')->store('lesson-plans', 'local')
            : null;

        $nextOrder = ((int) $teachingPlan->lessonPlans()->max('sort_order')) + 1;

        $teachingPlan->lessonPlans()->create([
            'learning_objective_id' => $validated['learning_objective_id'] ?? null,
            'title' => $validated['title'],
            'learning_goals' => $validated['learning_goals'] ?? null,
            'activities' => $validated['activities'] ?? null,
            'assessment_plan' => $validated['assessment_plan'] ?? null,
            'attachment_path' => $path,
            'external_url' => $validated['external_url'] ?? null,
            'sort_order' => $nextOrder,
        ]);

        return back()->with('status', 'Modul Ajar / RPP berhasil disimpan.');
    }

    public function updateLessonPlan(Request $request, TeachingPlan $teachingPlan, LessonPlan $lessonPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);
        abort_unless($lessonPlan->teaching_plan_id === $teachingPlan->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'learning_objective_id' => ['nullable', 'integer', 'exists:learning_objectives,id'],
            'learning_goals' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'assessment_plan' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
            'external_url' => ['nullable', 'url', 'max:500'],
        ]);

        $path = $lessonPlan->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
            $path = $request->file('attachment')->store('lesson-plans', 'local');
        }

        $lessonPlan->update([
            'learning_objective_id' => $validated['learning_objective_id'] ?? null,
            'title' => $validated['title'],
            'learning_goals' => $validated['learning_goals'] ?? null,
            'activities' => $validated['activities'] ?? null,
            'assessment_plan' => $validated['assessment_plan'] ?? null,
            'attachment_path' => $path,
            'external_url' => $validated['external_url'] ?? null,
        ]);

        return back()->with('status', 'Modul Ajar / RPP berhasil diperbarui.');
    }

    public function destroyLessonPlan(Request $request, TeachingPlan $teachingPlan, LessonPlan $lessonPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);
        abort_unless($lessonPlan->teaching_plan_id === $teachingPlan->id, 404);

        if ($lessonPlan->attachment_path && Storage::disk('local')->exists($lessonPlan->attachment_path)) {
            Storage::disk('local')->delete($lessonPlan->attachment_path);
        }

        $lessonPlan->delete();

        return back()->with('status', 'Modul Ajar berhasil dihapus.');
    }

    public function downloadLessonPlan(Request $request, TeachingPlan $teachingPlan, LessonPlan $lessonPlan): StreamedResponse
    {
        $this->authorizeAccess($request, $teachingPlan);
        abort_unless($lessonPlan->teaching_plan_id === $teachingPlan->id, 404);
        abort_unless($lessonPlan->attachment_path && Storage::disk('local')->exists($lessonPlan->attachment_path), 404);

        return Storage::disk('local')->download($lessonPlan->attachment_path);
    }

    public function publishToCourses(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'module_title' => ['required', 'string', 'max:255'],
            'material_title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:materi,lkpd,buku,presentasi,video'],
            'type' => ['required', 'in:text,file,link'],
            'content' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'file' => ['nullable', 'file', 'max:20480'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $courses = Course::query()
            ->whereIn('id', $validated['course_ids'])
            ->where('academic_year_id', $teachingPlan->academic_year_id)
            ->where('subject_id', $teachingPlan->subject_id)
            ->get();

        $storedFile = $request->hasFile('file')
            ? $request->file('file')->store('materials', 'local')
            : null;

        DB::transaction(function () use ($courses, $validated, $storedFile): void {
            foreach ($courses as $course) {
                $module = CourseModule::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $validated['module_title'],
                    ],
                    [
                        'sort_order' => ((int) $course->modules()->max('sort_order')) + 1,
                        'is_published' => (bool) ($validated['is_published'] ?? true),
                    ]
                );

                $filePath = null;
                if ($storedFile && Storage::disk('local')->exists($storedFile)) {
                    $filePath = 'materials/'.uniqid('parallel_', true).'_'.basename($storedFile);
                    Storage::disk('local')->copy($storedFile, $filePath);
                }

                Material::create([
                    'module_id' => $module->id,
                    'course_id' => $course->id,
                    'title' => $validated['material_title'],
                    'type' => $validated['type'],
                    'category' => $validated['category'],
                    'content' => $validated['content'] ?? null,
                    'file_path' => $filePath,
                    'external_url' => $validated['external_url'] ?? null,
                    'sort_order' => ((int) $module->materials()->max('sort_order')) + 1,
                    'is_published' => (bool) ($validated['is_published'] ?? true),
                ]);
            }
        });

        if ($storedFile && Storage::disk('local')->exists($storedFile)) {
            Storage::disk('local')->delete($storedFile);
        }

        return back()->with('status', "Materi berhasil diterbitkan serentak ke {$courses->count()} kelas paralel.");
    }

    public function toggleShare(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $teachingPlan->update(['is_shared' => ! $teachingPlan->is_shared]);

        return back()->with('status', $teachingPlan->is_shared
            ? 'Perangkat ajar sekarang dibagikan (hanya lihat & salin) ke rekan guru mapel ini.'
            : 'Perangkat ajar dikembalikan ke status Privat (hanya Anda yang dapat melihat).');
    }

    public function copyFrom(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'source_plan_id' => ['required', 'integer', 'exists:teaching_plans,id', 'not_in:'.$teachingPlan->id],
        ]);

        $sourcePlan = TeachingPlan::with(['learningObjectives', 'lessonPlans'])->findOrFail($validated['source_plan_id']);
        $user = $request->user();

        $canCopySource = $user->isAdmin()
            || $sourcePlan->teacher_id === $user->id
            || ($sourcePlan->is_shared && $sourcePlan->subject_id === $teachingPlan->subject_id && $sourcePlan->grade_level === $teachingPlan->grade_level);

        abort_unless($canCopySource, 403);

        DB::transaction(function () use ($sourcePlan, $teachingPlan): void {
            $teachingPlan->update([
                'phase' => $teachingPlan->phase ?: $sourcePlan->phase,
                'capaian_pembelajaran' => $sourcePlan->capaian_pembelajaran ?? $teachingPlan->capaian_pembelajaran,
                'effective_weeks' => $sourcePlan->effective_weeks ?? $teachingPlan->effective_weeks,
                'total_jp' => $sourcePlan->total_jp ?? $teachingPlan->total_jp,
                'kktp_type' => $sourcePlan->kktp_type,
                'kktp_min_score' => $sourcePlan->kktp_min_score,
                'kktp_notes' => $sourcePlan->kktp_notes ?? $teachingPlan->kktp_notes,
            ]);

            $objectiveMap = [];
            $nextObjOrder = ((int) $teachingPlan->learningObjectives()->max('sort_order')) + 1;
            foreach ($sourcePlan->learningObjectives as $srcObj) {
                $newObj = $teachingPlan->learningObjectives()->create([
                    'code' => $srcObj->code,
                    'title' => $srcObj->title,
                    'description' => $srcObj->description,
                    'jp_allocation' => $srcObj->jp_allocation,
                    'target_month' => $srcObj->target_month,
                    'sort_order' => $nextObjOrder++,
                ]);
                $objectiveMap[$srcObj->id] = $newObj->id;
            }

            $nextLpOrder = ((int) $teachingPlan->lessonPlans()->max('sort_order')) + 1;
            foreach ($sourcePlan->lessonPlans as $srcLp) {
                $newPath = null;
                if ($srcLp->attachment_path && Storage::disk('local')->exists($srcLp->attachment_path)) {
                    $newPath = 'lesson-plans/'.uniqid('copy_', true).'_'.basename($srcLp->attachment_path);
                    Storage::disk('local')->copy($srcLp->attachment_path, $newPath);
                }

                $teachingPlan->lessonPlans()->create([
                    'learning_objective_id' => $srcLp->learning_objective_id ? ($objectiveMap[$srcLp->learning_objective_id] ?? null) : null,
                    'title' => $srcLp->title,
                    'learning_goals' => $srcLp->learning_goals,
                    'activities' => $srcLp->activities,
                    'assessment_plan' => $srcLp->assessment_plan,
                    'attachment_path' => $newPath,
                    'sort_order' => $nextLpOrder++,
                ]);
            }
        });

        return back()->with('status', 'Perangkat ajar berhasil disalin ke perangkat milik Anda (status Privat).');
    }

    public function downloadTemplate(Request $request, TeachingPlan $teachingPlan, TeachingPlanExcelService $excel): StreamedResponse
    {
        $this->authorizeAccess($request, $teachingPlan);

        $spreadsheet = $excel->buildTemplateSpreadsheet($teachingPlan);
        $filename = 'template-perangkat-ajar-'.Str::slug($teachingPlan->title).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importPreview(Request $request, TeachingPlan $teachingPlan, TeachingPlanExcelService $excel): View
    {
        $this->authorizeOwner($request, $teachingPlan);

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $parsed = $excel->parseUploadedFile($request->file('excel_file')->getRealPath());
        $request->session()->put("teaching_plan_import_{$teachingPlan->id}", $parsed);

        return view('teaching-plans.import-preview', [
            'plan' => $teachingPlan,
            'parsed' => $parsed,
        ]);
    }

    public function importConfirm(Request $request, TeachingPlan $teachingPlan): RedirectResponse
    {
        $this->authorizeOwner($request, $teachingPlan);

        $validated = $request->validate([
            'import_mode' => ['required', 'in:append,replace'],
        ]);

        $parsed = $request->session()->pull("teaching_plan_import_{$teachingPlan->id}");
        if (! is_array($parsed) || ! empty($parsed['errors'])) {
            return redirect()->route('teaching-plans.show', $teachingPlan)
                ->withErrors(['excel_file' => 'Sesi pratinjau impor tidak valid atau sudah kedaluwarsa. Silakan unggah ulang file Excel.']);
        }

        DB::transaction(function () use ($teachingPlan, $parsed, $validated): void {
            $cp = $parsed['cp'];
            $teachingPlan->update([
                'title' => ($cp['title'] ?? '') !== '' ? $cp['title'] : $teachingPlan->title,
                'phase' => ($cp['phase'] ?? '') !== '' ? $cp['phase'] : $teachingPlan->phase,
                'capaian_pembelajaran' => ($cp['capaian_pembelajaran'] ?? '') !== '' ? $cp['capaian_pembelajaran'] : $teachingPlan->capaian_pembelajaran,
                'effective_weeks' => $cp['effective_weeks'] ?? $teachingPlan->effective_weeks,
                'total_jp' => $cp['total_jp'] ?? $teachingPlan->total_jp,
                'kktp_type' => $cp['kktp_type'] ?? $teachingPlan->kktp_type,
                'kktp_min_score' => $cp['kktp_min_score'] ?? $teachingPlan->kktp_min_score,
                'kktp_notes' => ($cp['kktp_notes'] ?? '') !== '' ? $cp['kktp_notes'] : $teachingPlan->kktp_notes,
            ]);

            if ($validated['import_mode'] === 'replace') {
                $teachingPlan->lessonPlans()->delete();
                $teachingPlan->learningObjectives()->delete();
            }

            $objectiveMap = $teachingPlan->learningObjectives()
                ->pluck('id', 'code')
                ->all();

            $nextObjOrder = ((int) $teachingPlan->learningObjectives()->max('sort_order')) + 1;
            foreach ($parsed['objectives'] as $obj) {
                $createdObj = LearningObjective::updateOrCreate(
                    [
                        'teaching_plan_id' => $teachingPlan->id,
                        'code' => $obj['code'],
                    ],
                    [
                        'title' => $obj['title'],
                        'description' => $obj['description'],
                        'jp_allocation' => $obj['jp_allocation'],
                        'target_month' => $obj['target_month'],
                        'sort_order' => $nextObjOrder++,
                    ]
                );
                $objectiveMap[$obj['code']] = $createdObj->id;
            }

            $nextLpOrder = ((int) $teachingPlan->lessonPlans()->max('sort_order')) + 1;
            foreach ($parsed['lesson_plans'] as $lp) {
                $teachingPlan->lessonPlans()->create([
                    'learning_objective_id' => $lp['tp_code'] ? ($objectiveMap[$lp['tp_code']] ?? null) : null,
                    'title' => $lp['title'],
                    'learning_goals' => $lp['learning_goals'],
                    'activities' => $lp['activities'],
                    'assessment_plan' => $lp['assessment_plan'],
                    'external_url' => $lp['external_url'],
                    'sort_order' => $nextLpOrder++,
                ]);
            }
        });

        return redirect()->route('teaching-plans.show', $teachingPlan)
            ->with('status', 'Perangkat ajar dari template Excel berhasil diimpor.');
    }

    private function authorizeAccess(Request $request, TeachingPlan $teachingPlan): void
    {
        $user = $request->user();
        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $teachingPlan->academic_year_id)
            || $user->hasStructuralRole('waka_kurikulum', $teachingPlan->academic_year_id);

        abort_unless($isLeadership || $teachingPlan->teacher_id === $user->id || $teachingPlan->is_shared, 403);
    }

    private function authorizeOwner(Request $request, TeachingPlan $teachingPlan): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $teachingPlan->teacher_id === $user->id, 403);
    }
}
