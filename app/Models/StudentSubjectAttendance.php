<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siap_record_id',
    'academic_year_id',
    'student_user_id',
    'classroom_id',
    'subject_id',
    'teacher_id',
    'attendance_date',
    'start_period',
    'end_period',
    'status',
    'topic',
    'notes',
    'synced_at',
])]
class StudentSubjectAttendance extends Model
{
    public const STATUS_LABELS = [
        'h' => 'Hadir',
        't' => 'Terlambat',
        's' => 'Sakit',
        'i' => 'Izin',
        'a' => 'Alpa',
        'd' => 'Dispen',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? strtoupper($this->status);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
