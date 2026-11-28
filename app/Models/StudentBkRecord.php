<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'record_type',
    'siap_record_id',
    'academic_year_id',
    'student_user_id',
    'record_date',
    'category',
    'level',
    'title',
    'summary',
    'recommendation',
    'status',
    'recorded_by_name',
    'synced_at',
])]
class StudentBkRecord extends Model
{
    public const TYPE_LABELS = [
        'achievement' => 'Prestasi / Kegiatan',
        'development_note' => 'Catatan Perkembangan',
        'counseling_service' => 'Layanan Konseling BK',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->record_type] ?? ucfirst($this->record_type);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
