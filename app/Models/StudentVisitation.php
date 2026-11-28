<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siap_visitation_id',
    'academic_year_id',
    'student_user_id',
    'visit_type',
    'visit_type_label',
    'reason',
    'officer_name',
    'planned_date',
    'executed_date',
    'status',
    'result_summary',
    'follow_up_plan',
    'synced_at',
])]
class StudentVisitation extends Model
{
    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'executed_date' => 'date',
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
