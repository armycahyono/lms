<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siap_permit_id',
    'academic_year_id',
    'student_user_id',
    'permit_number',
    'permit_date',
    'end_date',
    'permit_type',
    'permit_type_label',
    'permit_category',
    'reason',
    'entry_time',
    'departure_time',
    'return_time',
    'action_taken',
    'status',
    'giver_name',
    'synced_at',
])]
class StudentPermit extends Model
{
    protected function casts(): array
    {
        return [
            'permit_date' => 'date',
            'end_date' => 'date',
            'synced_at' => 'datetime',
        ];
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
