<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentPasswordController extends Controller
{
    public function reset(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isSiswa(), 422, 'Hanya akun siswa yang dapat direset ke NISN.');
        abort_unless($this->canResetStudentOrParent($request->user(), $student), 403);

        $student->update([
            'password' => Hash::make($student->username),
            'must_change_password' => true,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.reset_student_password',
            'subject_type' => User::class,
            'subject_id' => $student->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Kata sandi {$student->name} ({$student->username}) berhasil direset ke NISN.");
    }

    public function resetParent(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isSiswa(), 422, 'Hanya akun siswa yang memiliki tautan orang tua.');
        abort_unless($this->canResetStudentOrParent($request->user(), $student), 403);

        $parents = $student->parents()
            ->whereIn('role', ['orang_tua', 'wali_murid'])
            ->get();

        if ($parents->isEmpty()) {
            return back()->withErrors([
                'student' => "Siswa {$student->name} belum memiliki akun orang tua yang tertaut.",
            ]);
        }

        foreach ($parents as $parent) {
            $parent->update([
                'password' => Hash::make($student->username),
                'must_change_password' => true,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'admin.reset_parent_password',
                'subject_type' => User::class,
                'subject_id' => $parent->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return back()->with('status', "Kata sandi akun orang tua {$student->name} berhasil direset ke NISN ({$student->username}).");
    }

    private function canResetStudentOrParent(User $actor, User $student): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        if (! $actor->isGuru()) {
            return false;
        }

        $activeYear = AcademicYear::selectedFor($actor);
        $homeroomClassroomIds = UserAssignment::query()
            ->where('user_id', $actor->id)
            ->where('role_type', 'wali_kelas')
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->pluck('classroom_id')
            ->filter();

        if ($homeroomClassroomIds->isEmpty()) {
            return false;
        }

        return $student->classrooms()->whereIn('classrooms.id', $homeroomClassroomIds)->exists()
            || $student->studentClassHistories()
                ->whereIn('classroom_id', $homeroomClassroomIds)
                ->whereNull('left_at')
                ->exists();
    }
}
