<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'academic_year_id',
    'type',
    'course_id',
    'title',
    'description',
    'created_by',
    'is_locked',
    'direct_key',
    'last_message_at',
])]
class ChatRoom extends Model
{
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public static function directKeyFor(int $userA, int $userB): string
    {
        $ids = [$userA, $userB];
        sort($ids);

        return 'dm:'.$ids[0].':'.$ids[1];
    }
}
