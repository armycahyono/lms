<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $realActiveYearId = AcademicYear::active()?->id;

        $canMonitor = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $realActiveYearId)
            || $user->hasStructuralRole('waka_kurikulum', $realActiveYearId);

        abort_unless($canMonitor, 403);

        $yearId = $activeYear?->id;
        $search = trim((string) $request->query('q', ''));

        $kpis = [
            'active_courses' => Course::forAcademicYear($yearId)->where('status', 'active')->count(),
            'draft_courses' => Course::forAcademicYear($yearId)->where('status', 'draft')->count(),
            'teachers' => User::where('role', 'guru')->where('is_active', true)->count(),
            'students' => User::where('role', 'siswa')->where('is_active', true)->count(),
            'recent_activities' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $teachers = User::query()
            ->where('role', 'guru')
            ->where('is_active', true)
            ->withCount(['teachingCourses' => fn ($q) => $q->forAcademicYear($yearId)])
            ->orderBy('name')
            ->get()
            ->map(function (User $teacher) use ($yearId): array {
                $courseIds = $teacher->teachingCourses()->forAcademicYear($yearId)->pluck('courses.id')->all();

                return [
                    'teacher' => $teacher,
                    'courses_count' => $teacher->teaching_courses_count,
                    'materials_count' => $courseIds !== [] ? Material::whereIn('course_id', $courseIds)->where('is_published', true)->count() : 0,
                    'assignments_count' => $courseIds !== [] ? Assignment::whereIn('course_id', $courseIds)->where('is_published', true)->count() : 0,
                    'quizzes_count' => $courseIds !== [] ? Quiz::whereIn('course_id', $courseIds)->where('is_published', true)->count() : 0,
                    'ungraded_count' => $courseIds !== [] ? AssignmentSubmission::whereNull('graded_at')->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))->count() : 0,
                ];
            });

        $courses = Course::query()
            ->forAcademicYear($yearId)
            ->with(['classroom', 'subject', 'teachers'])
            ->withCount(['students', 'materials', 'assignments', 'quizzes', 'learningSessions'])
            ->latest('id')
            ->limit(150)
            ->get();

        $courseGradeGroups = $courses
            ->groupBy(function (Course $c): string {
                $grade = $c->classroom
                    ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                    : null;

                return (string) ($grade ?? 'other');
            })
            ->map(function ($byGrade): array {
                $first = $byGrade->first();
                $grade = $first->classroom
                    ? Classroom::parseGradeNumber($first->classroom->grade_level, $first->classroom->name)
                    : null;

                $subjects = $byGrade->groupBy(fn (Course $c): string => (string) ($c->subject_id ?? 0))
                    ->map(function ($bySubject): array {
                        /** @var Course $sample */
                        $sample = $bySubject->first();

                        return [
                            'subject_name' => $sample->subject?->name ?? 'Umum / Tanpa Mapel',
                            'courses' => $bySubject->sortBy(fn (Course $c) => $c->classroom?->name ?? $c->title)->values(),
                        ];
                    })
                    ->sortBy('subject_name')
                    ->values();

                return [
                    'grade_level' => $grade,
                    'subjects' => $subjects,
                    'total_courses' => $byGrade->count(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        $students = User::query()
            ->where('role', 'siswa')
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            }))
            ->with('classrooms')
            ->withCount(['enrolledCourses', 'materialProgress', 'assignmentSubmissions'])
            ->orderBy('name')
            ->limit(30)
            ->get();

        $recentLogs = ActivityLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit(25)
            ->get();

        return view('monitoring.index', [
            'activeYear' => $activeYear,
            'kpis' => $kpis,
            'teachers' => $teachers,
            'courses' => $courses,
            'courseGradeGroups' => $courseGradeGroups,
            'students' => $students,
            'recentLogs' => $recentLogs,
            'search' => $search,
        ]);
    }
}
