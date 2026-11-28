<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group_key',
    'label',
    'auto_sync_enabled',
    'schedule_minutes',
    'last_synced_at',
    'last_sync_mode',
    'last_summary_json',
])]
class SyncCheckpoint extends Model
{
    protected function casts(): array
    {
        return [
            'auto_sync_enabled' => 'boolean',
            'schedule_minutes' => 'integer',
            'last_synced_at' => 'datetime',
            'last_summary_json' => 'array',
        ];
    }
}
