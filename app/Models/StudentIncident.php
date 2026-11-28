<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siap_incident_id',
    'academic_year_id',
    'student_user_id',
    'incident_date',
    'incident_time',
    'location',
    'category',
    'description',
    'action_taken',
    'follow_up_target',
    'disciplinary_sanction',
    'parental_involvement',
    'parental_involvement_notes',
    'status',
    'recorder_name',
    'synced_at',
])]
class StudentIncident extends Model
{
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'parental_involvement' => 'boolean',
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
