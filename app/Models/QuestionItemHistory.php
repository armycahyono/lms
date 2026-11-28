<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'question_bank_id',
    'source_type',
    'source_id',
    'source_title',
    'academic_year_name',
    'total_answered',
    'total_correct',
    'difficulty_index',
    'option_distribution_json',
    'recorded_at',
])]
class QuestionItemHistory extends Model
{
    protected function casts(): array
    {
        return [
            'difficulty_index' => 'decimal:2',
            'option_distribution_json' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }
}
