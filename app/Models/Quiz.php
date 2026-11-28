<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id',
    'assessment_type',
    'learning_objective_id',
    'module_id',
    'title',
    'duration_minutes',
    'start_at',
    'end_at',
    'max_attempts',
    'shuffle_questions',
    'shuffle_options',
    'display_question_count',
    'passing_score',
    'quiz_type',
    'target_remedial_only',
    'parent_quiz_id',
    'is_published',
])]
class Quiz extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'target_remedial_only' => 'boolean',
            'passing_score' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    public function parentQuiz(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quiz_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
