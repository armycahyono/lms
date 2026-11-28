<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cbt_exam_result_id',
    'question_bank_id',
    'question_number',
    'question_type',
    'student_answer',
    'is_correct',
    'max_points',
    'earned_points',
    'teacher_feedback',
])]
class CbtExamAnswer extends Model
{
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'max_points' => 'decimal:2',
            'earned_points' => 'decimal:2',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(CbtExamResult::class, 'cbt_exam_result_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}
