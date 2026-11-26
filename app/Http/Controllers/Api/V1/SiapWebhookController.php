<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSiapWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiapWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => [
                'required',
                'string',
                'in:student.updated,student.mutated,classroom.updated,assignment.updated,academic_year.activated',
            ],
            'payload' => ['required', 'array'],
        ]);

        ProcessSiapWebhookJob::dispatch($validated['event'], $validated['payload']);

        return response()->json([
            'status' => 'queued',
            'event' => $validated['event'],
        ]);
    }
}
