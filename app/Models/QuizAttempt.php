<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'quiz_id',
    'student_id',
    'started_at',
    'submitted_at',
    'answers_json',
    'question_order_json',
    'question_scores_json',
    'score',
    'status',
    'focus_loss_count',
    'last_saved_at',
])]
class QuizAttempt extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_saved_at' => 'datetime',
            'answers_json' => 'array',
            'question_order_json' => 'array',
            'question_scores_json' => 'array',
            'score' => 'decimal:2',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
