<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'academic_year_id',
    'classroom_id',
    'subject_id',
    'title',
    'description',
    'status',
    'opened_at',
])]
class Course extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teachers', 'course_id', 'teacher_id')
            ->withPivot(['siap_assignment_id', 'is_primary'])
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'student_id')
            ->withPivot(['enrolled_at', 'status'])
            ->withTimestamps();
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }
}
