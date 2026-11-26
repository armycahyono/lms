<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors([
                    'username' => 'Username/NISN atau kata sandi salah, atau akun tidak aktif.',
                ]);
        }

        $request->session()->regenerate();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'auth.login',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->user()->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
