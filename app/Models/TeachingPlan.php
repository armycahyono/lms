<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'academic_year_id',
    'subject_id',
    'teacher_id',
    'grade_level',
    'phase',
    'title',
    'capaian_pembelajaran',
    'kktp_type',
    'kktp_min_score',
    'kktp_notes',
    'effective_weeks',
    'total_jp',
    'is_shared',
])]
class TeachingPlan extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function learningObjectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForAcademicYear(Builder $query, ?int $academicYearId): Builder
    {
        return $query->when($academicYearId !== null, fn (Builder $q) => $q->where('academic_year_id', $academicYearId));
    }
}
