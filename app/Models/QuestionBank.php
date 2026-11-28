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
    'stimulus_id',
    'grade_level',
    'department',
    'category',
    'kisi_indicator',
    'difficulty',
    'type',
    'question_text',
    'question_image',
    'options_json',
    'option_images_json',
    'correct_answer',
    'points',
    'is_shared',
    'usage_count',
    'total_answered',
    'total_correct',
    'option_stats_json',
    'last_used_at',
])]
class QuestionBank extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'multiple_choice' => 'Pilihan Ganda (A-E)',
        'multiple_choice_complex' => 'PG Kompleks',
        'matching' => 'Menjodohkan',
        'short_answer' => 'Isian Singkat',
        'essay' => 'Uraian / Esai',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'option_images_json' => 'array',
            'option_stats_json' => 'array',
            'points' => 'decimal:2',
            'is_shared' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function itemHistories(): HasMany
    {
        return $this->hasMany(QuestionItemHistory::class)->latest('recorded_at');
    }

    public function accuracyPercent(): ?float
    {
        if ($this->total_answered <= 0) {
            return null;
        }

        return round(($this->total_correct / $this->total_answered) * 100, 1);
    }

    public function empiricalDifficultyLabel(): string
    {
        $pct = $this->accuracyPercent();
        if ($pct === null) {
            return 'Belum Diuji';
        }

        return $pct >= 70 ? 'Mudah' : ($pct >= 30 ? 'Sedang' : 'Sulit');
    }

    public function stimulus(): BelongsTo
    {
        return $this->belongsTo(QuestionStimulus::class, 'stimulus_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
