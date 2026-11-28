<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'teacher_id',
    'learning_objective_id',
    'title',
    'topic',
    'journal_summary',
    'reflection',
    'is_achieved',
    'session_date',
    'opens_at',
    'closes_at',
    'is_open',
])]
class LearningSession extends Model
{
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'is_open' => 'boolean',
            'is_achieved' => 'boolean',
        ];
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LearningAttendance::class);
    }

    public function isAcceptingCheckIn(): bool
    {
        if (! $this->is_open) {
            return false;
        }

        $now = now();
        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }
        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        return true;
    }
}
