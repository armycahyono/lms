<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['profile', 'classrooms']);
        $activeYear = AcademicYear::where('is_active', true)->first();

        return view('profile.show', [
            'user' => $user,
            'activeYear' => $activeYear,
            'nameCardTemplates' => self::nameCardTemplates(),
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $templates = array_keys(self::nameCardTemplates());

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'avatar_scale' => ['nullable', 'integer', 'min:100', 'max:220'],
            'avatar_pos_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'avatar_pos_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'template' => ['nullable', 'string', 'in:'.implode(',', $templates)],
            'photo_pos' => ['nullable', 'string', 'in:top,left,right,none'],
            'photo_shape' => ['nullable', 'string', 'in:circle,rounded'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'animation' => ['nullable', 'string', 'in:none,wave,shimmer,aurora,float'],
        ]);

        $user = $request->user();
        $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);

        $avatarPath = $profile->avatar_path;

        if ($request->boolean('remove_avatar') && $avatarPath) {
            Storage::disk('public')->delete($avatarPath);
            $avatarPath = null;
        }

        if ($request->hasFile('avatar')) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $existing = is_array($profile->card_settings) ? $profile->card_settings : [];
        $accentColor = $validated['accent_color'] ?? $existing['accent_color'] ?? '#0284c7';
        $bgColor = $validated['bg_color'] ?? $existing['bg_color'] ?? '#ffffff';

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accentColor)) {
            $accentColor = '#0284c7';
        }
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $bgColor)) {
            $bgColor = '#ffffff';
        }

        $cardSettings = [
            'template' => $validated['template'] ?? $existing['template'] ?? 'classic',
            'photo_pos' => $validated['photo_pos'] ?? $existing['photo_pos'] ?? 'top',
            'photo_shape' => $validated['photo_shape'] ?? $existing['photo_shape'] ?? 'circle',
            'accent_color' => $accentColor,
            'bg_color' => $bgColor,
            'animation' => $validated['animation'] ?? $existing['animation'] ?? 'none',
            'avatar_scale' => (int) ($validated['avatar_scale'] ?? $existing['avatar_scale'] ?? 100),
            'avatar_pos_x' => (int) ($validated['avatar_pos_x'] ?? $existing['avatar_pos_x'] ?? 50),
            'avatar_pos_y' => (int) ($validated['avatar_pos_y'] ?? $existing['avatar_pos_y'] ?? 50),
        ];

        $profile->update([
            'avatar_path' => $avatarPath,
            'card_settings' => $cardSettings,
        ]);

        return back()->with('status', 'Tampilan kartu nama berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }

    public function updateNotepad(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'notepad' => ['nullable', 'string', 'max:50000'],
        ]);

        $profile = UserProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $profile->update([
            'notepad' => $validated['notepad'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'saved_at' => now()->format('H:i:s'),
            ]);
        }

        return redirect()->route('profile.show', ['tab' => 'notepad'])
            ->with('status', 'Catatan perbaikan berhasil disimpan.');
    }

    public static function isDarkHex(?string $hex): bool
    {
        if (! is_string($hex) || ! preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $matches)) {
            return false;
        }

        $r = hexdec(substr($matches[1], 0, 2));
        $g = hexdec(substr($matches[1], 2, 2));
        $b = hexdec(substr($matches[1], 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance < 0.55;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function nameCardTemplates(): array
    {
        return [
            'classic' => [
                'label' => 'Klasik Polos',
                'photo_pos' => 'top',
                'photo_shape' => 'circle',
                'accent_color' => '#0284c7',
                'bg_color' => '#ffffff',
                'animation' => 'none',
            ],
            'modern-side' => [
                'label' => 'Modern Kilau',
                'photo_pos' => 'left',
                'photo_shape' => 'rounded',
                'accent_color' => '#0284c7',
                'bg_color' => '#f8fafc',
                'animation' => 'shimmer',
            ],
            'ocean-wave' => [
                'label' => 'Gelombang Air',
                'photo_pos' => 'top',
                'photo_shape' => 'circle',
                'accent_color' => '#0284c7',
                'bg_color' => '#f0f9ff',
                'animation' => 'wave',
            ],
            'tech-dark' => [
                'label' => 'Elegan Gelap',
                'photo_pos' => 'right',
                'photo_shape' => 'rounded',
                'accent_color' => '#38bdf8',
                'bg_color' => '#0f172a',
                'animation' => 'aurora',
            ],
            'playful' => [
                'label' => 'Playful Gelembung',
                'photo_pos' => 'left',
                'photo_shape' => 'circle',
                'accent_color' => '#10b981',
                'bg_color' => '#ecfdf5',
                'animation' => 'float',
            ],
            'custom' => [
                'label' => 'Racik Sendiri',
                'photo_pos' => 'top',
                'photo_shape' => 'circle',
                'accent_color' => '#0284c7',
                'bg_color' => '#ffffff',
                'animation' => 'none',
            ],
        ];
    }
}
