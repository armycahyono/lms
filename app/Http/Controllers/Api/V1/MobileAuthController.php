<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('username', $validated['username'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'NISN/Username atau kata sandi salah.',
            ], 401);
        }

        $token = $user->createToken($validated['device_name'] ?? 'flutter-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'must_change_password' => (bool) $user->must_change_password,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
            ], 422);
        }

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui.',
            'must_change_password' => false,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Berhasil keluar.',
        ]);
    }
}
