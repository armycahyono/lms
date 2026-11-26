<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'quiz_id',
    'type',
    'question_text',
    'options_json',
    'correct_answer',
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
            'points' => 'decimal:2',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
