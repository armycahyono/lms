<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherPasswordController extends Controller
{
    public function reset(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($teacher->isGuru(), 422, 'Hanya akun guru yang dapat direset ke password default.');

        $teacher->update([
            'password' => Hash::make('password'),
            'must_change_password' => true,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin.reset_teacher_password',
            'subject_type' => User::class,
            'subject_id' => $teacher->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Kata sandi {$teacher->name} ({$teacher->username}) berhasil direset ke 'password'.");
    }
}
