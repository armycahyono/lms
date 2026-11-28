<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CbtExam;
use App\Models\CbtExamAnswer;
use App\Models\CbtExamResult;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\LmsNotificationService;
use App\Services\QuestionAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CbtExamController extends Controller
{
    public function index(Request $request, string $defaultTab = 'quizzes'): View
    {
        $user = $request->user();
        $selectedYearId = AcademicYear::selectedFor($user)?->id;
        $activeYearId = AcademicYear::active()?->id;
        $canManageAny = $user->isAdmin()
            || $user->isGuru()
            || $user->hasStructuralRole('waka_kurikulum', $activeYearId);

        $activeTab = (string) $request->query('tab', $defaultTab);
        if (! in_array($activeTab, ['quizzes', 'cbt'], true)) {
            $activeTab = 'quizzes';
        }

        if ($user->isSiswa()) {
            $courses = $user->enrolledCourses()->where('courses.status', 'active')->forAcademicYear($selectedYearId)->get();
            $courseIds = $courses->pluck('id');

            $quizzes = Quiz::query()
                ->where('is_published', true)
                ->whereIn('course_id', $courseIds)
                ->with([
                    'course.subject',
                    'course.classroom',
                    'attempts' => fn ($q) => $q->where('student_id', $user->id)->latest('id'),
                ])
                ->withCount('questions')
                ->latest('id')
                ->get();

            $exams = CbtExam::query()
                ->where('is_published', true)
                ->whereIn('course_id', $courseIds)
                ->with([
                    'course.subject',
                    'course.classroom',
                    'results' => fn ($q) => $q->where('student_id', $user->id),
                ])
                ->withCount('questions')
                ->latest('id')
                ->get()
                ->sortBy(function (CbtExam $exam): array {
                    $status = $exam->scheduleStatus($exam->results->first());

                    return [
                        $status['priority'],
                        $exam->start_at?->timestamp ?? PHP_INT_MAX,
                        -$exam->id,
                    ];
                })
                ->values();
        } else {
            $courses = ($user->isAdmin() || $user->hasStructuralRole('kepsek', $activeYearId) || $user->hasStructuralRole('waka_kurikulum', $activeYearId))
                ? Course::forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->where('status', 'active')->get()
                : $user->teachingCourses()->forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->get();

            $courseIds = $courses->pluck('id');

            $quizzes = Quiz::query()
                ->whereIn('course_id', $courseIds)
                ->with(['course.subject', 'course.classroom', 'parentQuiz'])
                ->withCount([
                    'questions',
                    'attempts',
                    'attempts as pending_essay_count' => fn ($q) => $q->where('status', 'submitted'),
                ])
                ->latest('id')
                ->get();

            $exams = CbtExam::query()
                ->whereIn('course_id', $courseIds)
                ->with([
                    'course.subject',
                    'course.classroom',
                    'course.students:id',
                    'questions:id,type',
                ])
                ->withCount(['results', 'questions'])
                ->latest('id')
                ->get();
        }

        $coursesBySubjectGrade = $courses->groupBy(function (Course $c): string {
            $grade = $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : '';

            return ($c->subject_id ?? 0).'|'.$grade;
        });

        $quizPackageGroups = $user->isSiswa() ? collect() : $quizzes->groupBy(function (Quiz $quiz): string {
            $grade = $quiz->course?->classroom
                ? Classroom::parseGradeNumber($quiz->course->classroom->grade_level, $quiz->course->classroom->name)
                : '';

            return implode('|', [
                mb_strtolower(trim($quiz->title)),
                (string) ($quiz->quiz_type ?? 'reguler'),
                (string) ($quiz->course?->subject_id ?? 0),
                (string) $grade,
            ]);
        })->map(function ($group) use ($coursesBySubjectGrade): array {
            /** @var Quiz $first */
            $first = $group->first();
            $subjectId = $first->course?->subject_id ?? 0;
            $grade = $first->course?->classroom
                ? Classroom::parseGradeNumber($first->course->classroom->grade_level, $first->course->classroom->name)
                : null;
            $sgKey = $subjectId.'|'.($grade ?? '');
            $expectedCourses = $coursesBySubjectGrade->get($sgKey, collect());
            $coveredCourseIds = $group->pluck('course_id')->unique();
            $missingCourses = $expectedCourses->filter(fn (Course $c): bool => ! $coveredCourseIds->contains($c->id))->values();

            return [
                'first_quiz' => $first,
                'quizzes' => $group,
                'title' => $first->title,
                'quiz_type' => $first->quiz_type ?? 'reguler',
                'target_remedial_only' => (bool) $first->target_remedial_only,
                'subject_id' => $subjectId,
                'subject_name' => $first->course?->subject?->name ?? $first->course?->title ?? '-',
                'grade_level' => $grade,
                'questions_count' => $first->questions_count,
                'duration_minutes' => $first->duration_minutes,
                'passing_score' => $first->passing_score ?? 75,
                'total_attempts' => $group->sum('attempts_count'),
                'pending_essay_count' => $group->sum('pending_essay_count'),
                'covered_count' => $coveredCourseIds->count(),
                'expected_count' => max($expectedCourses->count(), $coveredCourseIds->count()),
                'missing_courses' => $missingCourses,
            ];
        })->values();

        $examPackageGroups = $exams->groupBy(function (CbtExam $exam): string {
            $grade = $exam->course?->classroom
                ? Classroom::parseGradeNumber($exam->course->classroom->grade_level, $exam->course->classroom->name)
                : '';

            return implode('|', [
                mb_strtolower(trim($exam->title)),
                $exam->exam_type,
                (string) ($exam->course?->subject_id ?? 0),
                (string) $grade,
            ]);
        })->map(function ($group) use ($coursesBySubjectGrade): array {
            /** @var CbtExam $first */
            $first = $group->first();
            $subjectId = $first->course?->subject_id ?? 0;
            $grade = $first->course?->classroom
                ? Classroom::parseGradeNumber($first->course->classroom->grade_level, $first->course->classroom->name)
                : null;
            $sgKey = $subjectId.'|'.($grade ?? '');
            $expectedCourses = $coursesBySubjectGrade->get($sgKey, collect());
            $coveredCourseIds = $group->pluck('course_id')->unique();
            $missingCourses = $expectedCourses->filter(fn (Course $c): bool => ! $coveredCourseIds->contains($c->id))->values();

            $typeCounts = [
                'multiple_choice' => $first->questions->where('type', 'multiple_choice')->count(),
                'multiple_choice_complex' => $first->questions->where('type', 'multiple_choice_complex')->count(),
                'matching' => $first->questions->where('type', 'matching')->count(),
                'short_answer' => $first->questions->where('type', 'short_answer')->count(),
                'essay' => $first->questions->where('type', 'essay')->count(),
            ];

            $totalStudents = $group->flatMap(fn (CbtExam $e) => $e->course?->students?->pluck('id') ?? collect())
                ->unique()
                ->count();

            return [
                'first_exam' => $first,
                'exams' => $group,
                'title' => $first->title,
                'exam_type' => $first->exam_type,
                'subject_id' => $subjectId,
                'subject_name' => $first->course?->subject?->name ?? $first->course?->title ?? '-',
                'grade_level' => $grade,
                'total_questions' => $first->questions_count,
                'display_question_count' => $first->display_question_count,
                'type_counts' => $typeCounts,
                'total_students' => $totalStudents,
                'total_results' => $group->sum('results_count'),
                'synced_count' => $group->filter(fn (CbtExam $e) => $e->last_synced_at !== null)->count(),
                'covered_count' => $coveredCourseIds->count(),
                'expected_count' => max($expectedCourses->count(), $coveredCourseIds->count()),
                'missing_courses' => $missingCourses,
                'created_at' => $group->min('created_at'),
                'updated_at' => $group->max('updated_at'),
            ];
        })->values();

        $filterSubjects = $courses->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $filterGrades = $courses->map(function (Course $c): ?int {
            return $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : null;
        })->filter()->unique()->sort()->values();

        $quizSubjectGradeGroups = $user->isSiswa() ? collect() : $quizPackageGroups
            ->groupBy(fn (array $pkg): string => $pkg['subject_id'].'|'.($pkg['grade_level'] ?? ''))
            ->map(function ($items): array {
                $first = $items->first();

                return [
                    'subject_id' => $first['subject_id'],
                    'subject_name' => $first['subject_name'],
                    'grade_level' => $first['grade_level'],
                    'packages' => $items->values(),
                ];
            })
            ->sortBy([
                ['grade_level', 'asc'],
                ['subject_name', 'asc'],
            ])
            ->values();

        $quizGradeGroups = $user->isSiswa() ? collect() : $quizSubjectGradeGroups
            ->groupBy(fn (array $grp): string => (string) ($grp['grade_level'] ?? 'other'))
            ->map(function ($subjects): array {
                $first = $subjects->first();

                return [
                    'grade_level' => $first['grade_level'],
                    'subjects' => $subjects->values(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        $examSubjectGradeGroups = $user->isSiswa() ? collect() : $examPackageGroups
            ->groupBy(fn (array $pkg): string => $pkg['subject_id'].'|'.($pkg['grade_level'] ?? ''))
            ->map(function ($items): array {
                $first = $items->first();

                return [
                    'subject_id' => $first['subject_id'],
                    'subject_name' => $first['subject_name'],
                    'grade_level' => $first['grade_level'],
                    'packages' => $items->values(),
                ];
            })
            ->sortBy([
                ['grade_level', 'asc'],
                ['subject_name', 'asc'],
            ])
            ->values();

        $examGradeGroups = $user->isSiswa() ? collect() : $examSubjectGradeGroups
            ->groupBy(fn (array $grp): string => (string) ($grp['grade_level'] ?? 'other'))
            ->map(function ($subjects): array {
                $first = $subjects->first();

                return [
                    'grade_level' => $first['grade_level'],
                    'subjects' => $subjects->values(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        return view('exams.index', [
            'exams' => $exams,
            'examPackageGroups' => $examPackageGroups,
            'examSubjectGradeGroups' => $examSubjectGradeGroups,
            'examGradeGroups' => $examGradeGroups,
            'quizzes' => $quizzes,
            'quizPackageGroups' => $quizPackageGroups,
            'quizSubjectGradeGroups' => $quizSubjectGradeGroups,
            'quizGradeGroups' => $quizGradeGroups,
            'courses' => $courses,
            'filterSubjects' => $filterSubjects,
            'filterGrades' => $filterGrades,
            'canManageAny' => $canManageAny,
            'activeTab' => $activeTab,
            'cbtBaseUrl' => rtrim((string) config('services.cbt.url'), '/'),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $selectedYearId = AcademicYear::selectedFor($user)?->id;
        $activeYearId = AcademicYear::active()?->id;
        abort_unless(
            $user->isAdmin() || $user->isGuru() || $user->hasStructuralRole('waka_kurikulum', $activeYearId),
            403
        );

        $courses = ($user->isAdmin() || $user->hasStructuralRole('kepsek', $activeYearId) || $user->hasStructuralRole('waka_kurikulum', $activeYearId))
            ? Course::forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->where('status', 'active')->get()
            : $user->teachingCourses()->forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->get();

        $subjectGradeOptions = $courses
            ->filter(fn (Course $c) => $c->subject_id !== null)
            ->map(function (Course $c): array {
                $grade = $c->classroom
                    ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                    : null;

                return [
                    'key' => $c->subject_id.'|'.($grade ?? ''),
                    'subject_id' => (int) $c->subject_id,
                    'grade_level' => $grade ? (int) $grade : null,
                    'label' => ($c->subject?->name ?? 'Mapel').($grade ? ' — Kelas '.$grade : ''),
                ];
            })
            ->unique('key')
            ->sortBy('label')
            ->values();

        $coursesPayload = $courses->map(function (Course $c): array {
            $grade = $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : null;

            return [
                'id' => $c->id,
                'title' => $c->title,
                'classroom_name' => $c->classroom?->name ?? $c->title,
                'subject_id' => (int) $c->subject_id,
                'grade_level' => $grade ? (int) $grade : null,
            ];
        })->values();

        $taughtSubjectIds = $courses->pluck('subject_id')->filter()->unique()->all();
        $taughtGradeLevels = $courses->pluck('classroom')->filter()
            ->map(fn (Classroom $c) => Classroom::parseGradeNumber($c->grade_level, $c->name))
            ->filter(fn ($g) => in_array((int) $g, [10, 11, 12], true))
            ->unique()->sort()->values()->all();

        $questions = QuestionBank::query()
            ->when($user->isGuru() && $taughtSubjectIds !== [], function ($q) use ($user, $taughtSubjectIds, $taughtGradeLevels): void {
                $q->where(function ($sub) use ($user, $taughtSubjectIds, $taughtGradeLevels): void {
                    $sub->where(function ($own) use ($user, $taughtSubjectIds): void {
                        $own->where('teacher_id', $user->id)
                            ->where(fn ($s) => $s->whereIn('subject_id', $taughtSubjectIds)->orWhereNull('subject_id'));
                    })->orWhere(function ($shared) use ($taughtSubjectIds, $taughtGradeLevels): void {
                        $shared->where('is_shared', true)->whereIn('subject_id', $taughtSubjectIds);
                        if ($taughtGradeLevels !== []) {
                            $shared->where(fn ($g) => $g->whereIn('grade_level', $taughtGradeLevels)->orWhereNull('grade_level'));
                        }
                    });
                });
            })
            ->with(['subject', 'teacher'])
            ->latest('id')
            ->limit(300)
            ->get();

        return view('exams.create', [
            'courses' => $courses,
            'subjectGradeOptions' => $subjectGradeOptions,
            'coursesPayload' => $coursesPayload,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, LmsNotificationService $notifications, QuestionAnalysisService $analysis): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id', 'required_without:course_ids'],
            'course_ids' => ['nullable', 'array', 'min:1', 'required_without:course_id'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'cbt_exam_code' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', 'in:PTS,PAS,US,Simulasi,Lainnya'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'display_question_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'question_bank_ids' => ['nullable', 'array'],
            'question_bank_ids.*' => ['integer', 'exists:question_banks,id'],
        ]);

        $courseIds = ! empty($validated['course_ids'])
            ? $validated['course_ids']
            : [$validated['course_id']];

        $courses = Course::whereIn('id', $courseIds)->get();
        foreach ($courses as $course) {
            Gate::authorize('manage', $course);
        }

        $questionIds = array_values(array_unique(array_map('intval', $validated['question_bank_ids'] ?? [])));

        $firstExam = null;
        foreach ($courses as $course) {
            $exam = CbtExam::create([
                'course_id' => $course->id,
                'cbt_exam_code' => $validated['cbt_exam_code'] ?? null,
                'title' => $validated['title'],
                'exam_type' => $validated['exam_type'],
                'start_at' => $validated['start_at'] ?? null,
                'end_at' => $validated['end_at'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'display_question_count' => $validated['display_question_count'] ?? null,
                'launch_url' => $validated['launch_url'] ?? null,
                'is_published' => true,
            ]);

            if ($questionIds !== []) {
                foreach ($questionIds as $idx => $qId) {
                    $exam->questions()->attach($qId, ['sort_order' => $idx + 1]);
                }
            }

            $notifications->notifyCourseStudents(
                $course,
                'quiz',
                "Ujian Sekolah {$exam->exam_type}: {$exam->title}",
                "Ujian {$exam->title} di kelas {$course->title} dilaksanakan melalui CBTPGRI.",
                ['course_id' => $course->id, 'cbt_exam_id' => $exam->id]
            );

            $firstExam ??= $exam;
        }

        if ($questionIds !== []) {
            $analysis->incrementUsageCount($questionIds);
        }

        if ($courses->count() === 1 && $firstExam) {
            return redirect()->route('exams.show', $firstExam)
                ->with('status', $questionIds !== []
                    ? 'Ujian Sekolah (CBT) & '.count($questionIds).' soal berhasil disimpan. Klik Kirim Paket ke CBTPGRI.'
                    : 'Ujian Sekolah (CBT) berhasil dibuat. Silakan pilih soal dari Bank Soal.');
        }

        return redirect()->route('exams.index', ['tab' => 'cbt'])
            ->with('status', "Ujian Sekolah (CBT) berhasil dibuat untuk {$courses->count()} kelas.");
    }

    public function show(Request $request, CbtExam $cbtExam): View
    {
        Gate::authorize('view', $cbtExam->course);

        $canManage = Gate::allows('manage', $cbtExam->course) || $request->user()->isAdmin();
        abort_unless($canManage, 403);

        $cbtExam->load(['course.classroom', 'course.subject', 'questions', 'results.student', 'results.grader', 'results.answers.question']);
        $students = $cbtExam->course->students()->orderBy('name')->get();

        $availableBankQuestions = QuestionBank::query()
            ->where(function ($q) use ($request, $cbtExam): void {
                $q->where('teacher_id', $request->user()->id)
                    ->orWhere(function ($shared) use ($cbtExam): void {
                        $shared->where('is_shared', true)->where('subject_id', $cbtExam->course?->subject_id);
                    });
            })
            ->whereNotIn('id', $cbtExam->questions->pluck('id'))
            ->with('teacher')
            ->latest('id')
            ->limit(100)
            ->get();

        return view('exams.show', [
            'exam' => $cbtExam,
            'students' => $students,
            'resultsByStudent' => $cbtExam->results->keyBy('student_id'),
            'pendingEssayCount' => $cbtExam->results->where('status', 'menunggu_koreksi')->count(),
            'packageSiblings' => $this->siblingPackageExams($cbtExam),
        ]);
    }

    public function edit(Request $request, CbtExam $cbtExam): View
    {
        Gate::authorize('manage', $cbtExam->course);

        $cbtExam->loadMissing(['course.classroom', 'course.subject', 'questions', 'results']);
        $siblings = $this->siblingPackageExams($cbtExam);
        $siblings->load(['course.classroom', 'results']);

        $user = $request->user();
        $selectedYearId = $cbtExam->course?->academic_year_id ?? AcademicYear::selectedFor($user)?->id;
        $activeYearId = AcademicYear::active()?->id;

        $courses = ($user->isAdmin() || $user->hasStructuralRole('kepsek', $activeYearId) || $user->hasStructuralRole('waka_kurikulum', $activeYearId))
            ? Course::forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->where('status', 'active')->get()
            : $user->teachingCourses()->forAcademicYear($selectedYearId)->with(['classroom', 'subject'])->get();

        $targetSubjectId = $cbtExam->course?->subject_id;
        $targetGrade = $cbtExam->course?->classroom
            ? Classroom::parseGradeNumber($cbtExam->course->classroom->grade_level, $cbtExam->course->classroom->name)
            : null;

        $eligibleCourses = $courses->filter(function (Course $c) use ($targetSubjectId, $targetGrade): bool {
            if ($targetSubjectId && $c->subject_id !== $targetSubjectId) {
                return false;
            }
            if ($targetGrade !== null) {
                $g = $c->classroom ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name) : null;

                return $g === $targetGrade;
            }

            return true;
        })->sortBy(fn (Course $c) => $c->classroom?->name ?? $c->title)->values();

        $isLockedForRemoval = fn (CbtExam $s): bool => $s->results->isNotEmpty()
            || $s->last_synced_at !== null
            || $s->start_at !== null;

        return view('exams.edit', [
            'exam' => $cbtExam,
            'siblings' => $siblings,
            'eligibleCourses' => $eligibleCourses,
            'selectedCourseIds' => $siblings->pluck('course_id')->all(),
            'lockedCourseIds' => $siblings->filter($isLockedForRemoval)->pluck('course_id')->all(),
        ]);
    }

    public function update(Request $request, CbtExam $cbtExam): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', 'in:PTS,PAS,US,Simulasi,Lainnya'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:360'],
            'display_question_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'cbt_exam_code' => ['nullable', 'string', 'max:60'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $siblings = $this->siblingPackageExams($cbtExam);
        $siblings->load(['questions', 'results']);

        $targetCourseIds = collect($validated['course_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $courses = Course::whereIn('id', $targetCourseIds)->get();
        foreach ($courses as $course) {
            Gate::authorize('manage', $course);
        }

        $skippedRemoval = 0;

        $redirectExam = DB::transaction(function () use ($cbtExam, $siblings, $courses, $targetCourseIds, $validated, &$skippedRemoval): CbtExam {
            $questionSyncPayload = $cbtExam->questions()
                ->get()
                ->mapWithKeys(fn (QuestionBank $q): array => [$q->id => ['sort_order' => (int) $q->pivot->sort_order]])
                ->all();

            $existingByCourse = $siblings->keyBy('course_id');

            foreach ($siblings as $sibling) {
                if (! $targetCourseIds->contains($sibling->course_id)) {
                    $isLocked = $sibling->results->isNotEmpty()
                        || $sibling->last_synced_at !== null
                        || $sibling->start_at !== null;

                    if ($isLocked) {
                        $skippedRemoval++;
                    } else {
                        $sibling->delete();

                        continue;
                    }
                }

                $updateData = [
                    'title' => $validated['title'],
                    'exam_type' => $validated['exam_type'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'display_question_count' => $validated['display_question_count'] ?? null,
                ];

                if ($siblings->count() === 1 || $sibling->id === $cbtExam->id) {
                    if (array_key_exists('cbt_exam_code', $validated) && $validated['cbt_exam_code'] !== null && trim($validated['cbt_exam_code']) !== '') {
                        $updateData['cbt_exam_code'] = trim($validated['cbt_exam_code']);
                    }
                }

                $sibling->update($updateData);
            }

            foreach ($courses as $course) {
                if ($existingByCourse->has($course->id)) {
                    continue;
                }

                $newExam = CbtExam::create([
                    'course_id' => $course->id,
                    'cbt_exam_code' => null,
                    'title' => $validated['title'],
                    'exam_type' => $validated['exam_type'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'display_question_count' => $validated['display_question_count'] ?? null,
                    'launch_url' => $cbtExam->launch_url,
                    'is_published' => $cbtExam->is_published,
                ]);

                if ($questionSyncPayload !== []) {
                    $newExam->questions()->sync($questionSyncPayload);
                }
            }

            return CbtExam::find($cbtExam->id) ?? CbtExam::whereIn('course_id', $targetCourseIds)
                ->where('title', $validated['title'])
                ->where('exam_type', $validated['exam_type'])
                ->firstOrFail();
        });

        $msg = 'Pengaturan paket ujian sekolah berhasil diperbarui.';
        if ($skippedRemoval > 0) {
            $msg .= " ({$skippedRemoval} kelas tidak dilepas karena sudah dirilis ke CBTPGRI atau memiliki nilai siswa.)";
        }

        return redirect()->route('exams.show', $redirectExam)->with('status', $msg);
    }

    public function destroy(Request $request, CbtExam $cbtExam): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $siblings = $this->siblingPackageExams($cbtExam);
        $siblings->loadCount('results');

        if ($siblings->sum('results_count') > 0) {
            return back()->withErrors(['cbt' => 'Paket ujian tidak dapat dihapus karena sudah ada nilai siswa yang masuk.']);
        }

        $isReleasedToCbt = $siblings->contains(fn (CbtExam $s): bool => $s->last_synced_at !== null || $s->start_at !== null);
        if ($isReleasedToCbt && ! $request->user()->isAdmin()) {
            return back()->withErrors(['cbt' => 'Ujian yang sudah dirilis atau disinkronkan ke CBTPGRI tidak dapat dihapus oleh guru.']);
        }

        DB::transaction(function () use ($siblings): void {
            foreach ($siblings as $sibling) {
                $sibling->delete();
            }
        });

        return redirect()->route('exams.index', ['tab' => 'cbt'])
            ->with('status', 'Paket ujian sekolah berhasil dihapus.');
    }

    public function pickQuestions(Request $request, CbtExam $cbtExam): View
    {
        Gate::authorize('view', $cbtExam->course);
        $canManage = Gate::allows('manage', $cbtExam->course) || $request->user()->isAdmin();
        abort_unless($canManage, 403);

        $cbtExam->load(['course.classroom', 'course.subject', 'questions']);

        $user = $request->user();
        $courseSubjectId = $cbtExam->course?->subject_id;
        $courseGradeLevel = $cbtExam->course?->classroom
            ? Classroom::parseGradeNumber($cbtExam->course->classroom->grade_level, $cbtExam->course->classroom->name)
            : null;

        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $difficulty = trim((string) $request->query('difficulty', ''));
        $gradeLevel = $request->query('grade_level');
        $department = trim((string) $request->query('department', ''));
        $visibility = trim((string) $request->query('visibility', ''));
        $category = trim((string) $request->query('category', ''));

        $attachedIds = $cbtExam->questions->pluck('id')->all();

        $questions = QuestionBank::query()
            ->whereNotIn('id', $attachedIds)
            ->where(function ($q) use ($user, $courseSubjectId, $courseGradeLevel): void {
                $q->where(function ($own) use ($user, $courseSubjectId): void {
                    $own->where('teacher_id', $user->id);
                    if ($courseSubjectId) {
                        $own->where(fn ($s) => $s->where('subject_id', $courseSubjectId)->orWhereNull('subject_id'));
                    }
                })->orWhere(function ($shared) use ($courseSubjectId, $courseGradeLevel): void {
                    $shared->where('is_shared', true);
                    if ($courseSubjectId) {
                        $shared->where('subject_id', $courseSubjectId);
                    }
                    if ($courseGradeLevel) {
                        $shared->where(fn ($g) => $g->where('grade_level', $courseGradeLevel)->orWhereNull('grade_level'));
                    }
                });
            })
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('question_text', 'like', "%{$search}%")
                    ->orWhere('kisi_indicator', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($difficulty !== '', fn ($q) => $q->where('difficulty', $difficulty))
            ->when($gradeLevel, fn ($q) => $q->where('grade_level', (int) $gradeLevel))
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->when($category !== '', fn ($q) => $q->where('category', 'like', "%{$category}%"))
            ->when($visibility === 'private', fn ($q) => $q->where('teacher_id', $user->id)->where('is_shared', false))
            ->when($visibility === 'shared', fn ($q) => $q->where('is_shared', true))
            ->with(['teacher', 'subject'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $departments = Classroom::query()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->unique()
            ->values();

        return view('question-bank.picker', [
            'targetTitle' => $cbtExam->title,
            'targetSubtitle' => ($cbtExam->course?->subject?->name ?? 'Mapel').' · '.($cbtExam->course?->classroom?->name ?? '-').' ('.$cbtExam->exam_type.')',
            'existingCount' => count($attachedIds),
            'backUrl' => route('exams.show', $cbtExam),
            'submitUrl' => route('exams.questions.attach', $cbtExam),
            'questions' => $questions,
            'departments' => $departments,
            'gradeLevels' => $courseGradeLevel ? [$courseGradeLevel] : [10, 11, 12],
            'filters' => [
                'q' => $search,
                'type' => $type,
                'difficulty' => $difficulty,
                'grade_level' => $gradeLevel,
                'department' => $department,
                'visibility' => $visibility,
                'category' => $category,
            ],
        ]);
    }

    public function attachQuestions(Request $request, CbtExam $cbtExam, QuestionAnalysisService $analysis): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $validated = $request->validate([
            'question_bank_ids' => ['required', 'array', 'min:1'],
            'question_bank_ids.*' => ['integer', 'exists:question_banks,id'],
        ]);

        $user = $request->user();
        $items = QuestionBank::whereIn('id', $validated['question_bank_ids'])->get();
        $siblings = $this->siblingPackageExams($cbtExam);
        $attachedIds = [];

        foreach ($items as $item) {
            $allowed = $user->isAdmin()
                || $item->teacher_id === $user->id
                || ($item->is_shared && $item->subject_id === $cbtExam->course?->subject_id);
            abort_unless($allowed, 403);
        }

        foreach ($siblings as $targetExam) {
            $nextOrder = ((int) $targetExam->questions()->max('cbt_exam_questions.sort_order'));
            foreach ($items as $item) {
                if (! $targetExam->questions()->where('question_banks.id', $item->id)->exists()) {
                    $nextOrder++;
                    $targetExam->questions()->attach($item->id, ['sort_order' => $nextOrder]);
                    if ($targetExam->id === $cbtExam->id) {
                        $attachedIds[] = $item->id;
                    }
                }
            }
        }

        $analysis->incrementUsageCount($attachedIds);

        return redirect()->route('exams.show', $cbtExam)
            ->with('status', count($items).' butir soal berhasil ditambahkan ke paket ujian ('.$siblings->count().' kelas).');
    }

    public function detachQuestion(CbtExam $cbtExam, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $siblings = $this->siblingPackageExams($cbtExam);
        foreach ($siblings as $targetExam) {
            $targetExam->questions()->detach($questionBank->id);
        }

        return back()->with('status', 'Soal dikeluarkan dari paket ujian ('.$siblings->count().' kelas).');
    }

    public function pushToCbt(Request $request, CbtExam $cbtExam): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $cbtExam->load([
            'course.subject',
            'course.classroom',
            'course.teachers.profile',
            'course.students.profile',
            'questions',
        ]);

        if ($cbtExam->questions->isEmpty()) {
            return back()->withErrors(['cbt' => 'Tambahkan minimal 1 soal dari Bank Soal sebelum mengirim paket ke CBTPGRI.']);
        }

        $baseUrl = rtrim((string) config('services.cbt.url'), '/');
        $token = (string) config('services.cbt.token');

        if ($baseUrl === '') {
            return back()->withErrors(['cbt' => 'URL CBTPGRI belum dikonfigurasi.']);
        }

        $bankCode = $cbtExam->cbt_exam_code ?: sprintf('LMS-%d-%s', $cbtExam->id, strtoupper($cbtExam->exam_type));
        $gradeLevel = $cbtExam->course?->classroom
            ? Classroom::parseGradeNumber($cbtExam->course->classroom->grade_level, $cbtExam->course->classroom->name)
            : 10;

        $teacher = $cbtExam->course?->teachers->first() ?? $request->user();
        $teacherNigs = $teacher->profile?->nip_nisn ?: $teacher->username;
        $studentNisns = $cbtExam->course?->students
            ? $cbtExam->course->students->map(fn (User $s) => $s->profile?->nip_nisn ?: $s->username)->filter()->unique()->values()->all()
            : [];
        $studentManifest = $cbtExam->course?->students
            ? $cbtExam->course->students->map(fn (User $s): array => [
                'lms_student_id' => $s->id,
                'nisn' => $s->profile?->nip_nisn ?: $s->username,
                'username' => $s->username,
                'name' => $s->name,
            ])->values()->all()
            : [];

        $payload = [
            'bank_kode' => $bankCode,
            'bank_nama' => $cbtExam->title,
            'exam_type' => $cbtExam->exam_type,
            'grade_level' => (string) $gradeLevel,
            'classroom_name' => $cbtExam->course?->classroom?->name,
            'subject_code' => $cbtExam->course?->subject?->code,
            'subject_name' => $cbtExam->course?->subject?->name,
            'teacher_nigs' => $teacherNigs,
            'teacher_username' => $teacher->username,
            'teacher_name' => $teacher->name,
            'student_nisns' => $studentNisns,
            'students' => $studentManifest,
            'kkm' => 75,
            'display_question_count' => $cbtExam->display_question_count,
            'questions' => $cbtExam->questions->values()->map(function (QuestionBank $q, int $idx): array {
                $questionHtml = $q->question_text;

                $optionImages = [];
                foreach ($q->option_images_json ?? [] as $letter => $path) {
                    $encoded = $this->encodeLocalMedia($path);
                    if ($encoded !== null) {
                        $optionImages[$letter] = $encoded;
                    }
                }

                return [
                    'question_id' => $q->id,
                    'nomor_soal' => $idx + 1,
                    'type' => $q->type,
                    'question_text' => $questionHtml,
                    'question_image' => $this->encodeLocalMedia($q->question_image),
                    'options' => $q->options_json ?? [],
                    'option_images' => $optionImages,
                    'correct_answer' => $q->correct_answer,
                    'difficulty' => $q->difficulty,
                    'points' => (float) $q->points,
                ];
            })->all(),
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->post("{$baseUrl}/api/v1/lms/sync-paket-soal", $payload);

        if (! $response->successful()) {
            return back()->withErrors(['cbt' => 'Gagal mengirim paket soal ke CBTPGRI: '.($response->json('message') ?? $response->status())]);
        }

        $data = $response->json();

        $cbtExam->update([
            'cbt_exam_code' => $data['bank_kode'] ?? $bankCode,
            'cbt_package_id' => $data['id_bank'] ?? $cbtExam->cbt_package_id,
            'last_synced_at' => now(),
        ]);

        $msg = "Paket soal berhasil dikirim ke CBTPGRI (Kode: {$cbtExam->cbt_exam_code}).";
        if (! empty($data['sync_warning'])) {
            $msg .= ' Peringatan: '.$data['sync_warning'];
        }

        return back()->with('status', $msg);
    }

    public function pullScheduleFromCbt(CbtExam $cbtExam): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        if (! $cbtExam->cbt_exam_code) {
            return back()->withErrors(['cbt' => 'Kode ujian CBTPGRI belum tersedia. Kirim paket ke CBTPGRI terlebih dahulu.']);
        }

        $baseUrl = rtrim((string) config('services.cbt.url'), '/');
        $token = (string) config('services.cbt.token');

        if ($baseUrl === '') {
            return back()->withErrors(['cbt' => 'URL CBTPGRI belum dikonfigurasi.']);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get("{$baseUrl}/api/v1/lms/paket-soal/{$cbtExam->cbt_exam_code}/schedule");

        if (! $response->successful()) {
            return back()->withErrors(['cbt' => 'Gagal menarik jadwal dari CBTPGRI: '.($response->json('message') ?? $response->status())]);
        }

        $data = $response->json();
        $siblings = $this->siblingPackageExams($cbtExam);
        $updatePayload = array_filter([
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            'launch_url' => $data['launch_url'] ?? null,
            'session_name' => $data['session_name'] ?? null,
            'room_name' => $data['room_name'] ?? null,
            'last_synced_at' => now(),
        ], fn ($v) => $v !== null);

        foreach ($siblings as $sibling) {
            $sibling->update($updatePayload);
        }

        SyncLog::create([
            'source' => 'cbtpgri',
            'event_type' => 'pull_schedule',
            'status' => 'success',
            'summary_json' => [
                'cbt_exam_code' => $cbtExam->cbt_exam_code,
                'updated_exams' => $siblings->count(),
                'start_at' => $data['start_at'] ?? null,
                'end_at' => $data['end_at'] ?? null,
            ],
        ]);

        return back()->with('status', "Jadwal ujian CBTPGRI berhasil disinkronkan untuk {$siblings->count()} kelas.");
    }

    public function pullResultsFromCbt(Request $request, CbtExam $cbtExam): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        if (! $cbtExam->cbt_exam_code) {
            return back()->withErrors(['cbt' => 'Kode ujian CBTPGRI belum tersedia. Kirim paket atau isi kode ujian terlebih dahulu.']);
        }

        $baseUrl = rtrim((string) config('services.cbt.url'), '/');
        $token = (string) config('services.cbt.token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get("{$baseUrl}/api/v1/lms/paket-soal/{$cbtExam->cbt_exam_code}/results");

        if (! $response->successful()) {
            return back()->withErrors(['cbt' => 'Gagal menarik nilai dari CBTPGRI: '.($response->json('message') ?? $response->status())]);
        }

        $rows = $response->json('results', []);
        $courseStudents = $cbtExam->course->students()->with('profile')->get(['users.id', 'users.username', 'users.name']);
        $byId = $courseStudents->keyBy('id');
        $byUsername = $courseStudents->keyBy('username');
        $byNisn = $courseStudents->filter(fn (User $s) => ! empty($s->profile?->nip_nisn))
            ->keyBy(fn (User $s) => (string) $s->profile->nip_nisn);
        $examQuestionsByNumber = $cbtExam->questions()->get()->values();

        $synced = 0;
        $pendingEssay = 0;
        $unmatched = 0;
        $unmatchedSamples = [];

        foreach ($rows as $row) {
            $lmsStudentId = isset($row['lms_student_id']) ? (int) $row['lms_student_id'] : null;
            $nisn = trim((string) ($row['nisn'] ?? ''));
            $username = trim((string) ($row['username'] ?? ''));
            $studentName = trim((string) ($row['student_name'] ?? $row['name'] ?? ''));

            $student = ($lmsStudentId ? $byId->get($lmsStudentId) : null)
                ?? ($nisn !== '' ? $byNisn->get($nisn) : null)
                ?? ($username !== '' ? $byUsername->get($username) : null)
                ?? ($nisn !== '' ? $byUsername->get($nisn) : null);

            $answers = is_array($row['answers'] ?? null) ? $row['answers'] : [];
            $hasScore = isset($row['score']) || isset($row['total_score']) || isset($row['auto_score']);

            if (! $student || (! $hasScore && $answers === [])) {
                $unmatched++;
                if (count($unmatchedSamples) < 10) {
                    $unmatchedSamples[] = array_filter([
                        'name' => $studentName ?: null,
                        'nisn' => $nisn ?: null,
                        'username' => $username ?: null,
                    ]);
                }

                continue;
            }

            $needsEssayGrading = ($row['grading_status'] ?? null) === 'pending_essay'
                || collect($answers)->contains(function (array $ans): bool {
                    $type = (string) ($ans['type'] ?? $ans['question_type'] ?? '');

                    return $type === 'essay' && ! array_key_exists('earned_points', $ans);
                })
                || collect($answers)->contains(function (array $ans): bool {
                    $type = (string) ($ans['type'] ?? $ans['question_type'] ?? '');

                    return $type === 'essay' && $ans['earned_points'] === null;
                });

            $autoScore = isset($row['auto_score']) ? (float) $row['auto_score'] : (isset($row['score']) && ! $needsEssayGrading ? (float) $row['score'] : null);
            $essayScore = isset($row['essay_score']) ? (float) $row['essay_score'] : null;
            $displayScore = $needsEssayGrading
                ? ($autoScore ?? (isset($row['total_score']) ? (float) $row['total_score'] : (isset($row['score']) ? (float) $row['score'] : 0.0)))
                : (float) ($row['total_score'] ?? $row['score'] ?? $autoScore ?? 0.0);

            DB::transaction(function () use ($cbtExam, $student, $studentName, $displayScore, $autoScore, $essayScore, $needsEssayGrading, $row, $answers, $examQuestionsByNumber): void {
                $result = CbtExamResult::updateOrCreate(
                    [
                        'cbt_exam_id' => $cbtExam->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'cbt_student_name' => $studentName !== '' ? $studentName : null,
                        'score' => $displayScore,
                        'auto_score' => $autoScore,
                        'essay_score' => $essayScore,
                        'status' => $needsEssayGrading ? 'menunggu_koreksi' : 'selesai',
                        'finished_at' => ! empty($row['finished_at']) ? $row['finished_at'] : now(),
                    ]
                );

                foreach ($answers as $idx => $ans) {
                    $questionNumber = (int) ($ans['nomor_soal'] ?? $ans['question_number'] ?? ($idx + 1));
                    $questionBankId = isset($ans['question_id'])
                        ? (int) $ans['question_id']
                        : $examQuestionsByNumber->get($questionNumber - 1)?->id;

                    $studentAnswer = $ans['student_answer'] ?? null;
                    if (is_array($studentAnswer)) {
                        $studentAnswer = json_encode($studentAnswer, JSON_UNESCAPED_UNICODE);
                    }

                    CbtExamAnswer::updateOrCreate(
                        [
                            'cbt_exam_result_id' => $result->id,
                            'question_number' => $questionNumber,
                        ],
                        [
                            'question_bank_id' => $questionBankId,
                            'question_type' => (string) ($ans['type'] ?? $ans['question_type'] ?? 'multiple_choice'),
                            'student_answer' => $studentAnswer !== null ? (string) $studentAnswer : null,
                            'is_correct' => array_key_exists('is_correct', $ans) ? $ans['is_correct'] : null,
                            'max_points' => (float) ($ans['max_points'] ?? 1),
                            'earned_points' => isset($ans['earned_points']) ? (float) $ans['earned_points'] : null,
                        ]
                    );
                }
            });

            $synced++;
            if ($needsEssayGrading) {
                $pendingEssay++;
            }
        }

        SyncLog::create([
            'source' => 'cbtpgri',
            'event_type' => 'pull_results',
            'status' => $unmatched > 0 ? 'partial' : 'success',
            'summary_json' => [
                'cbt_exam_id' => $cbtExam->id,
                'cbt_exam_code' => $cbtExam->cbt_exam_code,
                'synced' => $synced,
                'pending_essay' => $pendingEssay,
                'unmatched' => $unmatched,
                'unmatched_samples' => $unmatchedSamples,
            ],
        ]);

        $msg = "Berhasil menarik {$synced} hasil siswa dari CBTPGRI";
        if ($pendingEssay > 0) {
            $msg .= " ({$pendingEssay} menunggu koreksi esai guru)";
        }
        $msg .= '.';
        if ($unmatched > 0) {
            $msg .= " {$unmatched} siswa dari CBTPGRI tidak ditemukan pada rombel kelas ini di LMS — cek SyncLog / sinkronisasi SIAP.";
        }

        return back()->with('status', $msg);
    }

    public function gradeResult(Request $request, CbtExamResult $cbtExamResult, LmsNotificationService $notifications): RedirectResponse
    {
        $cbtExamResult->loadMissing(['exam.course', 'student', 'answers']);
        Gate::authorize('manage', $cbtExamResult->exam->course);

        $validated = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'array'],
            'feedback.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $answersById = $cbtExamResult->answers->keyBy('id');
        foreach ($validated['scores'] as $answerId => $points) {
            $answer = $answersById->get((int) $answerId);
            if (! $answer) {
                return back()->withErrors(['scores' => 'Butir jawaban tidak valid untuk hasil ujian ini.']);
            }
            if ((float) $points > (float) $answer->max_points) {
                return back()->withErrors([
                    "scores.{$answerId}" => "Nilai soal #{$answer->question_number} tidak boleh melebihi bobot maksimal ({$answer->max_points}).",
                ])->withInput();
            }
        }

        $finalScore = DB::transaction(function () use ($request, $cbtExamResult, $validated, $answersById): float {
            $lockedResult = CbtExamResult::query()->whereKey($cbtExamResult->id)->lockForUpdate()->firstOrFail();

            foreach ($validated['scores'] as $answerId => $points) {
                $answer = $answersById->get((int) $answerId);
                $answer->update([
                    'earned_points' => (float) $points,
                    'teacher_feedback' => $validated['feedback'][$answerId] ?? $answer->teacher_feedback,
                ]);
            }

            $essayScore = (float) $lockedResult->answers()
                ->where('question_type', 'essay')
                ->sum('earned_points');

            $autoScore = (float) ($lockedResult->auto_score ?? 0);
            $totalScore = min(100.0, round($autoScore + $essayScore, 2));

            $lockedResult->update([
                'essay_score' => $essayScore,
                'score' => $totalScore,
                'status' => 'selesai',
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ]);

            return $totalScore;
        });

        if ($cbtExamResult->student) {
            $notifications->notifyUser(
                $cbtExamResult->student,
                'quiz',
                "Nilai Akhir Ujian {$cbtExamResult->exam->exam_type}: {$cbtExamResult->exam->title}",
                "Koreksi esai telah selesai. Nilai akhir Anda: {$finalScore}.",
                ['cbt_exam_id' => $cbtExamResult->cbt_exam_id]
            );
        }

        return back()->with('status', "Koreksi esai untuk {$cbtExamResult->student?->name} selesai (Nilai akhir: {$finalScore}).");
    }

    public function storeResult(Request $request, CbtExam $cbtExam, LmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('manage', $cbtExam->course);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $student = User::findOrFail($validated['student_id']);

        CbtExamResult::updateOrCreate(
            [
                'cbt_exam_id' => $cbtExam->id,
                'student_id' => $student->id,
            ],
            [
                'score' => $validated['score'],
                'status' => 'selesai',
                'finished_at' => now(),
            ]
        );

        $notifications->notifyUser(
            $student,
            'quiz',
            "Nilai Ujian {$cbtExam->exam_type}: {$cbtExam->title}",
            "Nilai ujian Anda telah tersedia: {$validated['score']}.",
            ['cbt_exam_id' => $cbtExam->id]
        );

        return back()->with('status', 'Hasil ujian CBTPGRI berhasil disimpan.');
    }

    /**
     * @return Collection<int, CbtExam>
     */
    private function siblingPackageExams(CbtExam $cbtExam): Collection
    {
        $cbtExam->loadMissing('course.classroom');
        $course = $cbtExam->course;
        if (! $course) {
            return collect([$cbtExam]);
        }

        $targetGrade = $course->classroom
            ? Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name)
            : null;
        $targetTitle = mb_strtolower(trim($cbtExam->title));

        return CbtExam::query()
            ->where('exam_type', $cbtExam->exam_type)
            ->whereHas('course', function ($q) use ($course): void {
                $q->where('academic_year_id', $course->academic_year_id)
                    ->where('subject_id', $course->subject_id);
            })
            ->with('course.classroom')
            ->get()
            ->filter(function (CbtExam $candidate) use ($targetTitle, $targetGrade): bool {
                if (mb_strtolower(trim($candidate->title)) !== $targetTitle) {
                    return false;
                }
                if ($targetGrade === null) {
                    return true;
                }
                $candidateGrade = $candidate->course?->classroom
                    ? Classroom::parseGradeNumber($candidate->course->classroom->grade_level, $candidate->course->classroom->name)
                    : null;

                return $candidateGrade === $targetGrade;
            })
            ->filter(fn (CbtExam $candidate): bool => Gate::allows('manage', $candidate->course))
            ->values();
    }

    /**
     * @return array{filename: string, base64: string}|null
     */
    private function encodeLocalMedia(?string $path): ?array
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return [
            'filename' => basename($path),
            'base64' => base64_encode(Storage::disk('local')->get($path)),
        ];
    }
}
