<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentPasswordController extends Controller
{
    public function reset(Request $request, User $student): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($student->isSiswa(), 422, 'Hanya akun siswa yang dapat direset ke NISN.');

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
}
