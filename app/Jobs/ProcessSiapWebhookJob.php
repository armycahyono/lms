<?php

namespace App\Jobs;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SyncLog;
use App\Services\SiapSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSiapWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $event,
        public array $payload,
    ) {}

    public function handle(SiapSyncService $syncService): void
    {
        try {
            match ($this->event) {
                'student.updated', 'student.mutated' => $syncService->upsertStudentFromPayload($this->payload),
                'classroom.updated' => $this->handleClassroomUpdated(),
                'assignment.updated' => $syncService->upsertTeachingAssignmentFromPayload($this->payload),
                'academic_year.activated' => $this->handleAcademicYearActivated(),
                default => null,
            };

            SyncLog::create([
                'source' => 'siap_webhook',
                'event_type' => $this->event,
                'status' => 'success',
                'summary_json' => $this->payload,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => 'siap_webhook',
                'event_type' => $this->event,
                'status' => 'failed',
                'summary_json' => $this->payload,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    private function handleClassroomUpdated(): void
    {
        $academicYear = AcademicYear::where('siap_id', $this->payload['academic_year_id'] ?? 0)->first();
        if (! $academicYear) {
            return;
        }

        Classroom::withTrashed()->updateOrCreate(
            ['siap_id' => $this->payload['siap_id']],
            [
                'academic_year_id' => $academicYear->id,
                'name' => $this->payload['name'],
                'grade_level' => $this->payload['grade_level'] ?? null,
                'department' => $this->payload['department'] ?? null,
                'deleted_at' => null,
            ]
        );
    }

    private function handleAcademicYearActivated(): void
    {
        $siapId = $this->payload['siap_id'] ?? null;
        if (! $siapId) {
            return;
        }

        AcademicYear::query()->update(['is_active' => false]);
        AcademicYear::updateOrCreate(
            ['siap_id' => $siapId],
            [
                'name' => $this->payload['name'] ?? '',
                'semester' => $this->payload['semester'] ?? 'Ganjil',
                'is_active' => true,
            ]
        );
    }
}
