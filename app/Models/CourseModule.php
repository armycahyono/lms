<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id',
    'title',
    'description',
    'sort_order',
    'is_published',
])]
class CourseModule extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'module_id')->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'module_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'module_id');
    }
}
