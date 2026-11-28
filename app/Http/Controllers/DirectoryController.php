<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isGuru(), 403);

        $activeYear = AcademicYear::selectedFor($user);
        $tab = (string) $request->query('tab', 'teachers');
        $search = trim((string) $request->query('q', ''));
        $classroomId = $request->query('classroom_id');

        $teachers = User::query()
            ->where('role', 'guru')
            ->where('is_active', true)
            ->when($search !== '' && $tab === 'teachers', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            }))
            ->with([
                'profile',
                'assignments' => fn ($q) => $q
                    ->when($activeYear, fn ($aq) => $aq->where('academic_year_id', $activeYear->id))
                    ->with('classroom'),
            ])
            ->orderBy('name')
            ->get();

        $students = User::query()
            ->where('role', 'siswa')
            ->forAcademicYear($activeYear)
            ->when($search !== '' && $tab === 'students', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            }))
            ->when($classroomId, fn ($q) => $q->where(function ($sub) use ($classroomId): void {
                $sub->whereHas('classrooms', fn ($cq) => $cq->where('classrooms.id', $classroomId))
                    ->orWhereHas('studentClassHistories', fn ($hq) => $hq->where('classroom_id', $classroomId));
            }))
            ->with([
                'profile',
                'classrooms' => fn ($q) => $q
                    ->when($activeYear, fn ($cq) => $cq->where('classrooms.academic_year_id', $activeYear->id))
                    ->with(['userAssignments' => fn ($uq) => $uq->where('role_type', 'wali_kelas')->with('user:id,name')]),
                'studentClassHistories' => fn ($q) => $q
                    ->when($activeYear, fn ($hq) => $hq->where('academic_year_id', $activeYear->id))
                    ->latest('id')
                    ->with(['classroom.userAssignments' => fn ($uq) => $uq->where('role_type', 'wali_kelas')->with('user:id,name')]),
                'parents.profile',
            ])
            ->orderBy('name')
            ->get();

        $classrooms = Classroom::query()
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderBy('name')
            ->get();

        return view('directory.index', [
            'activeYear' => $activeYear,
            'tab' => $tab,
            'search' => $search,
            'classroomId' => $classroomId,
            'teachers' => $teachers,
            'students' => $students,
            'classrooms' => $classrooms,
        ]);
    }
}
