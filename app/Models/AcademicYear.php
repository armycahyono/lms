<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'siap_id',
    'name',
    'semester',
    'is_active',
])]
class AcademicYear extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public static function active(): ?self
    {
        if (! Schema::hasTable('academic_years')) {
            return null;
        }

        return static::where('is_active', true)->first()
            ?? static::latest('id')->first();
    }

    public static function selectedFor(?User $user): ?self
    {
        if (! Schema::hasTable('academic_years')) {
            return null;
        }

        if ($user && ($user->isGuru() || $user->isAdmin())) {
            $selectedId = session('selected_academic_year_id');
            if ($selectedId) {
                $found = static::find($selectedId);
                if ($found) {
                    return $found;
                }
            }
        }

        return static::active();
    }
}
