<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CbtExam;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CbtScheduleWebhookController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.cbt.token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Token integrasi CBTPGRI tidak valid.'], 401);
        }

        $validated = $request->validate([
            'bank_kode' => ['required', 'string', 'max:60'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:360'],
            'launch_url' => ['nullable', 'url', 'max:500'],
            'session_name' => ['nullable', 'string', 'max:80'],
            'room_name' => ['nullable', 'string', 'max:80'],
        ]);

        $exams = CbtExam::where('cbt_exam_code', $validated['bank_kode'])->get();

        if ($exams->isEmpty()) {
            return response()->json(['message' => 'Paket ujian dengan kode tersebut tidak ditemukan di LMS.'], 404);
        }

        $updatePayload = array_filter([
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'launch_url' => $validated['launch_url'] ?? null,
            'session_name' => $validated['session_name'] ?? null,
            'room_name' => $validated['room_name'] ?? null,
            'last_synced_at' => now(),
        ], fn ($v) => $v !== null);

        foreach ($exams as $exam) {
            $exam->update($updatePayload);
        }

        SyncLog::create([
            'source' => 'cbtpgri',
            'event_type' => 'schedule_sync',
            'status' => 'success',
            'summary_json' => [
                'bank_kode' => $validated['bank_kode'],
                'updated_exams' => $exams->count(),
                'start_at' => $validated['start_at'] ?? null,
                'end_at' => $validated['end_at'] ?? null,
            ],
        ]);

        return response()->json([
            'ok' => true,
            'updated_exams' => $exams->count(),
        ]);
    }
}
