<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'teaching_plan_id',
    'code',
    'title',
    'description',
    'jp_allocation',
    'target_month',
    'sort_order',
])]
class LearningObjective extends Model
{
    public function teachingPlan(): BelongsTo
    {
        return $this->belongsTo(TeachingPlan::class);
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class);
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }
}
