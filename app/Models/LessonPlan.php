<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'teaching_plan_id',
    'learning_objective_id',
    'title',
    'learning_goals',
    'activities',
    'assessment_plan',
    'attachment_path',
    'external_url',
    'sort_order',
])]
class LessonPlan extends Model
{
    public function teachingPlan(): BelongsTo
    {
        return $this->belongsTo(TeachingPlan::class);
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class);
    }
}
