<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningAttendance;
use App\Models\LearningSession;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $yearId = AcademicYear::selectedFor($user)?->id;

        if ($user->isSiswa()) {
            $courses = $user->enrolledCourses()
                ->where('courses.status', 'active')
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom'])
                ->withCount([
                    'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                    'assignments as published_assignments_count' => fn ($q) => $q->where('is_published', true),
                    'learningSessions as sessions_count',
                ])
                ->get();

            $courseIds = $courses->pluck('id')->all();
            $completedMaterialIds = MaterialProgress::where('student_id', $user->id)->pluck('material_id')->all();

            $completedByCourse = Material::query()
                ->whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->whereIn('id', $completedMaterialIds)
                ->get(['id', 'course_id'])
                ->groupBy('course_id')
                ->map->count();

            $submittedByCourse = AssignmentSubmission::query()
                ->where('student_id', $user->id)
                ->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
                ->with('assignment:id,course_id')
                ->get()
                ->groupBy(fn ($s) => $s->assignment?->course_id)
                ->map->count();

            $attendedByCourse = LearningAttendance::query()
                ->where('student_id', $user->id)
                ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
                ->with('session:id,course_id')
                ->get()
                ->groupBy(fn ($a) => $a->session?->course_id)
                ->map->count();

            $courseProgressList = $courses->map(function ($course) use ($completedByCourse, $submittedByCourse, $attendedByCourse): array {
                $totalMat = (int) $course->published_materials_count;
                $doneMat = (int) ($completedByCourse[$course->id] ?? 0);
                $totalAss = (int) $course->published_assignments_count;
                $doneAss = (int) ($submittedByCourse[$course->id] ?? 0);
                $totalSes = (int) $course->sessions_count;
                $doneSes = (int) ($attendedByCourse[$course->id] ?? 0);

                return [
                    'course' => $course,
                    'materials_done' => $doneMat,
                    'materials_total' => $totalMat,
                    'materials_pct' => $totalMat > 0 ? (int) round(($doneMat / $totalMat) * 100) : 0,
                    'assignments_done' => $doneAss,
                    'assignments_total' => $totalAss,
                    'attendance_done' => $doneSes,
                    'attendance_total' => $totalSes,
                ];
            });

            $recentActivities = ActivityLog::where('user_id', $user->id)
                ->latest('created_at')
                ->limit(10)
                ->get();

            return view('progress.index', [
                'mode' => 'student',
                'courseProgressList' => $courseProgressList,
                'recentActivities' => $recentActivities,
                'summary' => [
                    'materials_done' => $completedByCourse->sum(),
                    'materials_total' => $courses->sum('published_materials_count'),
                    'assignments_done' => AssignmentSubmission::where('student_id', $user->id)->count(),
                    'assignments_total' => Assignment::whereIn('course_id', $courseIds)->where('is_published', true)->count(),
                    'quizzes_done' => QuizAttempt::where('student_id', $user->id)->whereNotNull('submitted_at')->count(),
                    'attendance_done' => LearningAttendance::where('student_id', $user->id)->count(),
                    'attendance_total' => LearningSession::whereIn('course_id', $courseIds)->count(),
                ],
            ]);
        }

        // Guru / Admin
        $coursesQuery = $user->isGuru()
            ? $user->teachingCourses()->forAcademicYear($yearId)
            : Course::query()->forAcademicYear($yearId)->where('status', 'active');

        $courses = $coursesQuery
            ->with(['subject', 'classroom'])
            ->withCount(['students', 'materials', 'assignments', 'learningSessions'])
            ->get();

        $courseGroups = $courses->groupBy(function (Course $course): string {
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
            ];
        })->sortBy([
            ['grade_level', 'asc'],
            ['subject_name', 'asc'],
        ])->values();

        $gradeGroups = $courseGroups
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

        return view('progress.index', [
            'mode' => 'teacher',
            'courses' => $courses,
            'courseGroups' => $courseGroups,
            'gradeGroups' => $gradeGroups,
        ]);
    }
}
