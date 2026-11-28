<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'quiz_id',
    'question_bank_id',
    'stimulus_id',
    'type',
    'question_text',
    'question_image',
    'options_json',
    'option_images_json',
    'correct_answer',
    'rubric_keywords',
    'points',
    'sort_order',
])]
class QuizQuestion extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'option_images_json' => 'array',
            'points' => 'decimal:2',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function stimulus(): BelongsTo
    {
        return $this->belongsTo(QuestionStimulus::class, 'stimulus_id');
    }
}
