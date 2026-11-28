<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CbtExam;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->isOrangTua()) {
            return redirect()->route('parent.index');
        }

        $activeYear = AcademicYear::selectedFor($user);
        $yearId = $activeYear?->id;

        if ($user->isAdmin()) {
            $search = trim((string) $request->query('q', ''));
            $students = User::query()
                ->where('role', 'siswa')
                ->forAcademicYear($activeYear)
                ->with([
                    'classrooms' => fn ($q) => $q
                        ->when($yearId !== null, fn ($cq) => $cq->where('classrooms.academic_year_id', $yearId))
                        ->with(['userAssignments' => fn ($uq) => $uq->where('role_type', 'wali_kelas')->with('user:id,name')]),
                    'studentClassHistories' => fn ($q) => $q
                        ->when($yearId !== null, fn ($hq) => $hq->where('academic_year_id', $yearId))
                        ->latest('id')
                        ->with(['classroom.userAssignments' => fn ($uq) => $uq->where('role_type', 'wali_kelas')->with('user:id,name')]),
                    'parents.profile',
                ])
                ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->limit(20)
                ->get();

            $schoolAnnouncements = Announcement::query()
                ->whereNull('course_id')
                ->when($yearId !== null, fn ($q) => $q->where(fn ($sub) => $sub->where('academic_year_id', $yearId)->orWhereNull('academic_year_id')))
                ->with('user')
                ->latest('is_pinned')
                ->latest('id')
                ->limit(5)
                ->get();

            return view('dashboard', [
                'activeYear' => $activeYear,
                'stats' => [
                    'teachers' => User::where('role', 'guru')->count(),
                    'students' => User::where('role', 'siswa')->forAcademicYear($activeYear)->count(),
                    'active_courses' => Course::forAcademicYear($yearId)->where('status', 'active')->count(),
                    'draft_courses' => Course::forAcademicYear($yearId)->where('status', 'draft')->count(),
                    'recent_activities' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
                    'published_quizzes' => Quiz::where('is_published', true)->whereHas('course', fn ($q) => $q->forAcademicYear($yearId))->count(),
                ],
                'students' => $students,
                'search' => $search,
                'schoolAnnouncements' => $schoolAnnouncements,
            ]);
        }

        if ($user->isGuru()) {
            $teachingCourses = $user->teachingCourses()
                ->forAcademicYear($yearId)
                ->with(['classroom', 'subject', 'academicYear'])
                ->withCount([
                    'students',
                    'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                    'assignments',
                    'quizzes as published_quizzes_count' => fn ($q) => $q->where('is_published', true),
                ])
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
                ->get();

            $isSupervisory = $user->hasStructuralRole('kepsek', $yearId)
                || $user->hasStructuralRole('waka_kurikulum', $yearId);

            $schoolStats = $isSupervisory ? [
                'total_courses' => Course::forAcademicYear($yearId)->count(),
                'active_courses' => Course::forAcademicYear($yearId)->where('status', 'active')->count(),
                'draft_courses' => Course::forAcademicYear($yearId)->where('status', 'draft')->count(),
                'total_students' => User::where('role', 'siswa')->forAcademicYear($activeYear)->count(),
            ] : null;

            $pendingSubmissionsQuery = AssignmentSubmission::query()
                ->whereNull('graded_at')
                ->whereHas('assignment.course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                });

            $ungradedCount = (clone $pendingSubmissionsQuery)->count();

            $pendingSubmissions = (clone $pendingSubmissionsQuery)
                ->with(['student', 'assignment.course.classroom'])
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            $activeQuizzes = Quiz::query()
                ->where('is_published', true)
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()))
                ->with(['course.subject', 'course.classroom'])
                ->withCount('attempts')
                ->orderByRaw('CASE WHEN end_at IS NULL THEN 1 ELSE 0 END, end_at ASC')
                ->limit(4)
                ->get();

            $recentActivities = AssignmentSubmission::query()
                ->whereHas('assignment.course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->with(['student', 'assignment.course'])
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            $pendingAssignments = Assignment::query()
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->whereHas('submissions', fn ($q) => $q->whereNull('graded_at'))
                ->with(['course.classroom', 'course.subject'])
                ->withCount(['submissions as ungraded_count' => fn ($q) => $q->whereNull('graded_at')])
                ->latest('id')
                ->limit(6)
                ->get();

            $pendingEssayExams = CbtExam::query()
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->whereHas('results', fn ($q) => $q->where('status', 'menunggu_koreksi'))
                ->with(['course.classroom', 'course.subject'])
                ->withCount(['results as pending_essay_count' => fn ($q) => $q->where('status', 'menunggu_koreksi')])
                ->latest('id')
                ->limit(6)
                ->get();

            $teachingCourseGroups = $teachingCourses->groupBy(function (Course $course): string {
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
                    'subject_name' => $first->subject?->name ?? $first->title,
                    'grade_level' => $grade,
                    'courses' => $sorted,
                    'students_count' => $sorted->sum('students_count'),
                    'max_materials' => (int) $sorted->max('published_materials_count'),
                    'max_assignments' => (int) $sorted->max('assignments_count'),
                ];
            })->sortBy([
                ['subject_name', 'asc'],
                ['grade_level', 'asc'],
            ])->values();

            return view('dashboard', [
                'activeYear' => $activeYear,
                'teachingCourses' => $teachingCourses,
                'teachingCourseGroups' => $teachingCourseGroups,
                'isSupervisory' => $isSupervisory,
                'schoolStats' => $schoolStats,
                'ungradedCount' => $ungradedCount,
                'teacherSummary' => [
                    'courses_count' => $teachingCourses->count(),
                    'groups_count' => $teachingCourseGroups->count(),
                    'students_count' => $teachingCourses->sum('students_count'),
                    'materials_count' => $teachingCourses->sum('published_materials_count'),
                    'active_quizzes_count' => $activeQuizzes->count(),
                ],
                'pendingSubmissions' => $pendingSubmissions,
                'pendingAssignments' => $pendingAssignments,
                'pendingEssayExams' => $pendingEssayExams,
                'activeQuizzes' => $activeQuizzes,
                'recentActivities' => $recentActivities,
            ]);
        }

        // Siswa
        $enrolledCourses = $user->enrolledCourses()
            ->where('courses.status', 'active')
            ->forAcademicYear($yearId)
            ->with(['classroom', 'subject', 'teachers'])
            ->withCount([
                'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                'assignments as published_assignments_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->get();

        $courseIds = $enrolledCourses->pluck('id')->all();

        $completedMaterialIds = MaterialProgress::where('student_id', $user->id)
            ->pluck('material_id')
            ->all();

        $totalPublishedMaterials = $enrolledCourses->sum('published_materials_count');
        $completedInEnrolled = Material::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->whereIn('id', $completedMaterialIds)
            ->get(['id', 'course_id']);

        $completedCountByCourse = $completedInEnrolled->groupBy('course_id')->map->count();
        $overallProgress = $totalPublishedMaterials > 0
            ? (int) round(($completedInEnrolled->count() / $totalPublishedMaterials) * 100)
            : 0;

        $pendingAssignments = Assignment::query()
            ->where('is_published', true)
            ->whereIn('course_id', $courseIds)
            ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $user->id))
            ->with(['course.subject', 'course.classroom'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at ASC')
            ->limit(5)
            ->get();

        $upcomingQuizzes = Quiz::query()
            ->where('is_published', true)
            ->whereIn('course_id', $courseIds)
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()))
            ->with([
                'course.subject',
                'attempts' => fn ($q) => $q->where('student_id', $user->id)->latest(),
            ])
            ->withCount('questions')
            ->orderByRaw('CASE WHEN end_at IS NULL THEN 1 ELSE 0 END, end_at ASC')
            ->limit(4)
            ->get();

        $lastMaterialProgress = MaterialProgress::query()
            ->where('student_id', $user->id)
            ->whereHas('material', fn ($q) => $q->whereIn('course_id', $courseIds)->where('is_published', true))
            ->with('material.course.subject')
            ->latest('completed_at')
            ->first();

        $nextMaterial = $lastMaterialProgress?->material
            ?? Material::query()
                ->whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->with('course.subject')
                ->latest()
                ->first();

        $schoolAnnouncements = Announcement::query()
            ->whereNull('course_id')
            ->when($yearId !== null, fn ($q) => $q->where(fn ($sub) => $sub->where('academic_year_id', $yearId)->orWhereNull('academic_year_id')))
            ->with('user')
            ->latest('is_pinned')
            ->latest('id')
            ->limit(3)
            ->get();

        $classAnnouncements = Announcement::query()
            ->whereIn('course_id', $courseIds)
            ->with(['user', 'course.subject'])
            ->latest('is_pinned')
            ->latest('id')
            ->limit(4)
            ->get();

        $announcements = Discussion::query()
            ->whereIn('course_id', $courseIds)
            ->whereNull('parent_id')
            ->whereHas('user', fn ($q) => $q->where('role', 'guru'))
            ->with(['user', 'course.subject'])
            ->latest()
            ->limit(4)
            ->get();

        return view('dashboard', [
            'activeYear' => $activeYear,
            'enrolledCourses' => $enrolledCourses,
            'completedCountByCourse' => $completedCountByCourse,
            'overallProgress' => $overallProgress,
            'completedMaterialsCount' => $completedInEnrolled->count(),
            'totalPublishedMaterials' => $totalPublishedMaterials,
            'pendingAssignments' => $pendingAssignments,
            'upcomingQuizzes' => $upcomingQuizzes,
            'lastMaterialProgress' => $lastMaterialProgress,
            'nextMaterial' => $nextMaterial,
            'announcements' => $announcements,
            'schoolAnnouncements' => $schoolAnnouncements,
            'classAnnouncements' => $classAnnouncements,
        ]);
    }
}
