<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siap_event_id',
    'academic_year_id',
    'title',
    'description',
    'category',
    'category_label',
    'start_date',
    'end_date',
    'start_time',
    'end_time',
    'is_all_day',
    'is_holiday',
    'color',
    'location',
    'synced_at',
])]
class SchoolCalendarEvent extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_all_day' => 'boolean',
            'is_holiday' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
