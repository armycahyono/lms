<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'source',
    'event_type',
    'status',
    'summary_json',
    'error_message',
    'created_at',
])]
class SyncLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
