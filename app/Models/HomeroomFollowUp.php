<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'academic_year_id',
    'student_user_id',
    'teacher_id',
    'follow_up_date',
    'action_type',
    'notes',
    'status',
])]
class HomeroomFollowUp extends Model
{
    public const ACTION_LABELS = [
        'komunikasi_ortu' => 'Komunikasi Orang Tua',
        'pembinaan' => 'Pembinaan Siswa',
        'kunjungan_rumah' => 'Rencana Visitasi / Home Visit',
        'rujuk_bk' => 'Koordinasi / Rujuk BK',
        'lainnya' => 'Tindak Lanjut Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action_type] ?? ucfirst(str_replace('_', ' ', $this->action_type));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
