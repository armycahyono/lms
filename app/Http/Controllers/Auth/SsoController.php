<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Services\SiapSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function callback(Request $request, SiapSyncService $syncService): RedirectResponse
    {
        $code = (string) $request->query('code', '');

        if (strlen($code) < 32) {
            return redirect()->route('login')->withErrors([
                'username' => 'Kode autentikasi SSO tidak valid.',
            ]);
        }

        $response = $syncService->client()->post('/api/integration/lms/sso/verify', [
            'code' => $code,
        ]);

        if (! $response->successful()) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sesi SSO kedaluwarsa atau gagal diverifikasi oleh SIAP.',
            ]);
        }

        $payload = $response->json();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $user = $syncService->upsertTeacherFromPayload($payload, $activeYear);

        if (! $user || ! $user->is_active || ! in_array($user->role, ['guru', 'admin', 'siswa', 'orang_tua'], true)) {
            return redirect()->route('login')->withErrors([
                'username' => 'Akun staf / tenaga kependidikan tidak memiliki akses ke LMS.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'sso.login',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
