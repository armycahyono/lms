<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'cbt_exam_code',
    'cbt_package_id',
    'title',
    'exam_type',
    'start_at',
    'end_at',
    'duration_minutes',
    'session_name',
    'room_name',
    'display_question_count',
    'launch_url',
    'is_published',
    'last_synced_at',
])]
class CbtExam extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_published' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionBank::class, 'cbt_exam_questions')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('cbt_exam_questions.sort_order');
    }

    public function results(): HasMany
    {
        return $this->hasMany(CbtExamResult::class);
    }

    /**
     * @return array{key: string, label: string, badge: string, can_launch: bool, priority: int}
     */
    public function scheduleStatus(?CbtExamResult $result = null): array
    {
        if ($result) {
            return [
                'key' => 'finished',
                'label' => $result->isPendingEssay() ? 'Menunggu Koreksi Esai' : 'Selesai',
                'badge' => $result->isPendingEssay() ? 'bg-warning-lt' : 'bg-success-lt',
                'can_launch' => false,
                'priority' => 3,
            ];
        }

        if (! $this->start_at) {
            return [
                'key' => 'unscheduled',
                'label' => 'Menunggu Jadwal',
                'badge' => 'bg-secondary-lt',
                'can_launch' => false,
                'priority' => 2,
            ];
        }

        $now = now();
        if ($now->lt($this->start_at)) {
            return [
                'key' => 'upcoming',
                'label' => 'Akan Datang',
                'badge' => 'bg-warning-lt',
                'can_launch' => false,
                'priority' => 1,
            ];
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return [
                'key' => 'ended',
                'label' => 'Jadwal Berakhir',
                'badge' => 'bg-danger-lt',
                'can_launch' => false,
                'priority' => 4,
            ];
        }

        return [
            'key' => 'ongoing',
            'label' => 'Sedang Berlangsung',
            'badge' => 'bg-success-lt',
            'can_launch' => true,
            'priority' => 0,
        ];
    }
}
