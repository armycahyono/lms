<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\QuizAttempt;
use App\Models\RemedialEnrichment;
use App\Models\TeachingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $selectedYear = AcademicYear::selectedFor($user);
        $selectedYearId = $selectedYear?->id;

        $query = Course::query()
            ->forAcademicYear($selectedYearId)
            ->with([
                'classroom.userAssignments' => fn ($q) => $q->where('role_type', 'wali_kelas')->with('user'),
                'subject',
                'academicYear',
                'teachers',
            ])
            ->withCount([
                'students',
                'materials',
                'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                'assignments',
            ]);

        if ($user->isSiswa()) {
            $query->where('status', 'active')
                ->whereHas('students', fn ($q) => $q->where('users.id', $user->id)->where('course_enrollments.status', 'active'));
        } elseif ($user->isGuru() && ! $user->hasStructuralRole('kepsek', $selectedYearId) && ! $user->hasStructuralRole('waka_kurikulum', $selectedYearId)) {
            $query->where(function ($q) use ($user, $selectedYearId) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereIn(
                        'classroom_id',
                        $user->assignments()
                            ->where('role_type', 'wali_kelas')
                            ->when($selectedYearId, fn ($aq) => $aq->where('academic_year_id', $selectedYearId))
                            ->pluck('classroom_id')
                    );
            });
        }

        $courses = $query->latest('id')->get();

        $homeroomClassroomIds = $user->isGuru()
            ? $user->assignments()
                ->where('role_type', 'wali_kelas')
                ->when($selectedYearId, fn ($aq) => $aq->where('academic_year_id', $selectedYearId))
                ->pluck('classroom_id')
                ->filter()
                ->all()
            : [];

        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $selectedYearId)
            || $user->hasStructuralRole('waka_kurikulum', $selectedYearId);

        if ($user->isGuru() && ! $isLeadership) {
            $taughtCourses = $courses->filter(fn (Course $course) => $course->teachers->contains('id', $user->id))->values();
            $homeroomCourses = $courses->filter(fn (Course $course) => in_array($course->classroom_id, $homeroomClassroomIds, true))->values();
        } elseif ($user->isGuru() && $homeroomClassroomIds !== []) {
            $taughtCourses = $courses;
            $homeroomCourses = $courses->filter(fn (Course $course) => in_array($course->classroom_id, $homeroomClassroomIds, true))->values();
        } else {
            $taughtCourses = $courses;
            $homeroomCourses = collect();
        }

        $completedCountByCourse = collect();
        if ($user->isSiswa() && $courses->isNotEmpty()) {
            $completedMaterialIds = MaterialProgress::where('student_id', $user->id)
                ->pluck('material_id')
                ->all();

            $completedCountByCourse = Material::query()
                ->whereIn('course_id', $courses->pluck('id'))
                ->where('is_published', true)
                ->whereIn('id', $completedMaterialIds)
                ->get(['id', 'course_id'])
                ->groupBy('course_id')
                ->map->count();
        }

        $taughtCourseGroups = $user->isSiswa() ? collect() : $taughtCourses->groupBy(function (Course $course): string {
            $grade = $course->classroom
                ? Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name)
                : '';

            return ($course->subject_id ?? 0).'|'.$grade;
        })->map(function ($group): array {
            /** @var Course $first */
            $first = $group->first();
            $grade = $first->classroom
                ? Classroom::parseGradeNumber($first->classroom->grade_level, $first->classroom->name)
                : null;
            $sorted = $group->sortBy(fn (Course $c) => $c->classroom?->name ?? $c->title)->values();

            return [
                'subject_id' => $first->subject_id ?? 0,
                'subject_name' => $first->subject?->name ?? $first->title,
                'grade_level' => $grade,
                'courses' => $sorted,
                'students_count' => $sorted->sum('students_count'),
                'max_materials' => (int) $sorted->max('published_materials_count'),
                'max_assignments' => (int) $sorted->max('assignments_count'),
            ];
        })->sortBy([
            ['grade_level', 'asc'],
            ['subject_name', 'asc'],
        ])->values();

        $taughtGradeGroups = $user->isSiswa() ? collect() : $taughtCourseGroups
            ->groupBy(fn (array $grp): string => (string) ($grp['grade_level'] ?? 'other'))
            ->map(function ($subjects): array {
                $first = $subjects->first();

                return [
                    'grade_level' => $first['grade_level'],
                    'subjects' => $subjects->values(),
                    'total_courses' => $subjects->sum(fn (array $s): int => $s['courses']->count()),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        $filterSubjects = $taughtCourses->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $filterGrades = $taughtCourses->map(function (Course $c): ?int {
            return $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : null;
        })->filter()->unique()->sort()->values();

        return view('courses.index', [
            'courses' => $taughtCourses,
            'taughtCourses' => $taughtCourses,
            'taughtCourseGroups' => $taughtCourseGroups,
            'taughtGradeGroups' => $taughtGradeGroups,
            'homeroomCourses' => $homeroomCourses,
            'completedCountByCourse' => $completedCountByCourse,
            'filterSubjects' => $filterSubjects,
            'filterGrades' => $filterGrades,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $user = $request->user();
        $canManage = Gate::allows('manage', $course);

        if ($user->isSiswa()) {
            LearningAttendanceController::recordAutoDigital($course, $user, $request->ip());
        }

        $course->load([
            'classroom',
            'subject',
            'academicYear',
            'teachers',
            'modules' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->orderBy('sort_order'),
            'modules.materials' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->orderBy('sort_order'),
            'assignments' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->latest('id'),
            'quizzes' => fn ($q) => $q->when(! $canManage, fn ($sub) => $sub->where('is_published', true))->latest('id'),
            'discussions' => fn ($q) => $q->whereNull('discussable_type')->whereNull('parent_id')->with(['user', 'replies.user'])->latest('id'),
            'announcements.user',
            'learningSessions' => fn ($q) => $q->with('learningObjective')->withCount('attendances')
                ->when($user->isSiswa(), fn ($sub) => $sub->with(['attendances' => fn ($aq) => $aq->where('student_id', $user->id)])),
        ]);

        $completedMaterialIds = $user->isSiswa()
            ? MaterialProgress::where('student_id', $user->id)
                ->whereIn('material_id', $course->materials()->pluck('id'))
                ->pluck('material_id')
                ->all()
            : [];

        $duplicableCourses = $canManage
            ? Course::where('id', '!=', $course->id)
                ->whereHas('modules')
                ->with(['classroom', 'subject', 'academicYear'])
                ->get()
            : collect();

        $unassignedMaterials = $course->materials()
            ->whereNull('module_id')
            ->when(! $canManage, fn ($sub) => $sub->where('is_published', true))
            ->orderBy('sort_order')
            ->get();

        $courseGradeLevel = $course->classroom
            ? Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name)
            : null;

        $teachingPlan = TeachingPlan::query()
            ->where('academic_year_id', $course->academic_year_id)
            ->where('subject_id', $course->subject_id)
            ->when($courseGradeLevel !== null, fn ($q) => $q->where('grade_level', $courseGradeLevel))
            ->with('learningObjectives')
            ->first();

        $remedialEnrichments = $canManage
            ? RemedialEnrichment::query()
                ->where('course_id', $course->id)
                ->with(['student', 'learningObjective'])
                ->latest('id')
                ->get()
            : collect();

        $isCurrentAcademicYear = (bool) $course->academicYear?->is_active;

        $courseStudents = $canManage
            ? $course->students()
                ->when($isCurrentAcademicYear, fn ($q) => $q
                    ->wherePivot('status', 'active')
                    ->where('users.is_active', true))
                ->orderBy('name')
                ->get(['users.id', 'users.name'])
            : collect();

        $kktpMinScore = $teachingPlan?->kktp_min_score ?? 75;
        $studentKktpSummary = collect();
        if ($canManage && $courseStudents->isNotEmpty()) {
            $assignmentScores = AssignmentSubmission::query()
                ->whereIn('assignment_id', $course->assignments->pluck('id'))
                ->whereNotNull('score')
                ->get(['student_id', 'score'])
                ->groupBy('student_id');

            $quizScores = QuizAttempt::query()
                ->whereIn('quiz_id', $course->quizzes->pluck('id'))
                ->whereNotNull('score')
                ->get(['student_id', 'score'])
                ->groupBy('student_id');

            $studentKktpSummary = $courseStudents->map(function ($st) use ($assignmentScores, $quizScores, $kktpMinScore) {
                $scores = collect()
                    ->merge(($assignmentScores->get($st->id) ?? collect())->pluck('score'))
                    ->merge(($quizScores->get($st->id) ?? collect())->pluck('score'))
                    ->map(fn ($v) => (float) $v);

                $avg = $scores->isNotEmpty() ? round($scores->avg(), 1) : null;

                return [
                    'student' => $st,
                    'avg_score' => $avg,
                    'is_passed' => $avg !== null ? $avg >= $kktpMinScore : null,
                ];
            });
        }

        return view('courses.show', [
            'course' => $course,
            'canManage' => $canManage,
            'completedMaterialIds' => $completedMaterialIds,
            'duplicableCourses' => $duplicableCourses,
            'unassignedMaterials' => $unassignedMaterials,
            'teachingPlan' => $teachingPlan,
            'learningObjectives' => $teachingPlan?->learningObjectives ?? collect(),
            'kktpMinScore' => $kktpMinScore,
            'remedialEnrichments' => $remedialEnrichments,
            'courseStudents' => $courseStudents,
            'studentKktpSummary' => $studentKktpSummary,
        ]);
    }

    public function open(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $enrolledCount = DB::transaction(function () use ($course): int {
            $course->update([
                'status' => 'active',
                'opened_at' => $course->opened_at ?? now(),
            ]);

            $studentIds = ClassroomStudent::where('classroom_id', $course->classroom_id)
                ->where('status', 'active')
                ->whereHas('student', fn ($q) => $q->where('is_active', true))
                ->pluck('student_user_id');

            $count = 0;
            foreach ($studentIds as $studentId) {
                CourseEnrollment::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'enrolled_at' => now(),
                        'status' => 'active',
                    ]
                );
                $count++;
            }

            return $count;
        });

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'course.opened',
            'subject_type' => Course::class,
            'subject_id' => $course->id,
            'properties_json' => ['enrolled_students' => $enrolledCount],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Kelas dibuka dan {$enrolledCount} siswa rombel otomatis didaftarkan.");
    }

    public function archive(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $course->update(['status' => 'archived']);

        return back()->with('status', 'Kelas berhasil diarsipkan.');
    }

    public function duplicate(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'source_course_id' => ['required', 'integer', 'exists:courses,id', 'not_in:'.$course->id],
        ]);

        $sourceCourse = Course::with(['modules.materials'])->findOrFail($validated['source_course_id']);

        DB::transaction(function () use ($sourceCourse, $course): void {
            foreach ($sourceCourse->modules as $sourceModule) {
                $newModule = CourseModule::create([
                    'course_id' => $course->id,
                    'title' => $sourceModule->title,
                    'description' => $sourceModule->description,
                    'sort_order' => $sourceModule->sort_order,
                    'is_published' => false,
                ]);

                foreach ($sourceModule->materials as $sourceMaterial) {
                    $newFilePath = null;
                    if ($sourceMaterial->file_path && Storage::disk('local')->exists($sourceMaterial->file_path)) {
                        $newFilePath = 'materials/'.uniqid('copy_', true).'_'.basename($sourceMaterial->file_path);
                        Storage::disk('local')->copy($sourceMaterial->file_path, $newFilePath);
                    }

                    Material::create([
                        'module_id' => $newModule->id,
                        'course_id' => $course->id,
                        'title' => $sourceMaterial->title,
                        'type' => $sourceMaterial->type,
                        'content' => $sourceMaterial->content,
                        'file_path' => $newFilePath,
                        'external_url' => $sourceMaterial->external_url,
                        'sort_order' => $sourceMaterial->sort_order,
                        'is_published' => false,
                    ]);
                }
            }
        });

        return back()->with('status', "Modul dan materi berhasil diduplikasi dari {$sourceCourse->title} (status draft).");
    }
}
