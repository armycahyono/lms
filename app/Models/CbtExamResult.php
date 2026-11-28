<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cbt_exam_id',
    'student_id',
    'cbt_student_name',
    'score',
    'auto_score',
    'essay_score',
    'status',
    'finished_at',
    'graded_by',
    'graded_at',
])]
class CbtExamResult extends Model
{
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'auto_score' => 'decimal:2',
            'essay_score' => 'decimal:2',
            'finished_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(CbtExam::class, 'cbt_exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CbtExamAnswer::class)->orderBy('question_number');
    }

    public function isPendingEssay(): bool
    {
        return $this->status === 'menunggu_koreksi';
    }
}
