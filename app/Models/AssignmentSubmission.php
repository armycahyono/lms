<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'assignment_id',
    'student_id',
    'content',
    'file_path',
    'submitted_at',
    'is_late',
    'score',
    'feedback',
    'graded_at',
    'graded_by',
    'is_validated_for_siap',
])]
class AssignmentSubmission extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
            'is_validated_for_siap' => 'boolean',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
