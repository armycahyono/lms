<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\MaterialProgress;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYear = AcademicYear::where('is_active', true)->first();

        if ($user->isAdmin()) {
            $search = trim((string) $request->query('q', ''));
            $students = User::query()
                ->where('role', 'siswa')
                ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->limit(20)
                ->get();

            return view('dashboard', [
                'activeYear' => $activeYear,
                'stats' => [
                    'teachers' => User::where('role', 'guru')->count(),
                    'students' => User::where('role', 'siswa')->count(),
                    'active_courses' => Course::where('status', 'active')->count(),
                    'draft_courses' => Course::where('status', 'draft')->count(),
                ],
                'recentSyncLogs' => SyncLog::latest('created_at')->limit(5)->get(),
                'students' => $students,
                'search' => $search,
            ]);
        }

        if ($user->isGuru()) {
            $teachingCourses = $user->teachingCourses()
                ->with(['classroom', 'subject', 'academicYear'])
                ->withCount(['students', 'materials', 'assignments'])
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
                ->get();

            $isSupervisory = $user->hasStructuralRole('kepsek', $activeYear?->id)
                || $user->hasStructuralRole('waka_kurikulum', $activeYear?->id);

            $schoolStats = $isSupervisory ? [
                'total_courses' => Course::count(),
                'active_courses' => Course::where('status', 'active')->count(),
                'draft_courses' => Course::where('status', 'draft')->count(),
                'total_students' => User::where('role', 'siswa')->where('is_active', true)->count(),
            ] : null;

            $ungradedCount = AssignmentSubmission::query()
                ->whereNull('graded_at')
                ->whereHas('assignment.course.teachers', fn ($q) => $q->where('users.id', $user->id))
                ->count();

            return view('dashboard', [
                'activeYear' => $activeYear,
                'teachingCourses' => $teachingCourses,
                'isSupervisory' => $isSupervisory,
                'schoolStats' => $schoolStats,
                'ungradedCount' => $ungradedCount,
            ]);
        }

        // Siswa
        $enrolledCourses = $user->enrolledCourses()
            ->where('courses.status', 'active')
            ->with(['classroom', 'subject', 'teachers'])
            ->withCount([
                'materials as published_materials_count' => fn ($q) => $q->where('is_published', true),
                'assignments as published_assignments_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->get();

        $completedMaterialIds = MaterialProgress::where('student_id', $user->id)
            ->pluck('material_id')
            ->all();

        return view('dashboard', [
            'activeYear' => $activeYear,
            'enrolledCourses' => $enrolledCourses,
            'completedMaterialIds' => $completedMaterialIds,
        ]);
    }
}
