<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'siap_id',
    'academic_year_id',
    'name',
    'grade_level',
    'department',
])]
class Classroom extends Model
{
    use SoftDeletes;

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_students', 'classroom_id', 'student_user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserAssignment::class);
    }
}
