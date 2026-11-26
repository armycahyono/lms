<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'siap_id',
    'code',
    'name',
])]
class Subject extends Model
{
    use SoftDeletes;

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
