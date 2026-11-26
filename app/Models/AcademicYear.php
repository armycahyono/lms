<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'siap_id',
    'name',
    'semester',
    'is_active',
])]
class AcademicYear extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
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
