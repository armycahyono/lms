<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'academic_year_id',
    'coordinator_id',
    'title',
    'theme',
    'phase',
    'dimensions',
    'objectives',
    'activities',
    'assessment_notes',
    'attachment_path',
    'status',
])]
class ProjectModule extends Model
{
    use SoftDeletes;

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function scopeForAcademicYear(Builder $query, ?int $academicYearId): Builder
    {
        return $query->when($academicYearId !== null, fn (Builder $q) => $q->where('academic_year_id', $academicYearId));
    }
}
