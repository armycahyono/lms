<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'nip_nisn',
    'phone',
    'address',
    'avatar_path',
    'card_bg',
    'card_bg_image',
    'card_settings',
    'bio',
    'notepad',
])]
class UserProfile extends Model
{
    protected function casts(): array
    {
        return [
            'card_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
