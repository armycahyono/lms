<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Kata sandi baru tidak boleh sama dengan kata sandi awal.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'auth.password_changed',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
