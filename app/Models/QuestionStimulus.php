<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'teacher_id',
    'subject_id',
    'grade_level',
    'title',
    'content_html',
    'image_path',
    'is_shared',
])]
class QuestionStimulus extends Model
{
    use SoftDeletes;

    protected $table = 'question_stimuli';

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'stimulus_id');
    }
}
