<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            if ($course->teachers()->where('users.id', $user->id)->exists()) {
                return true;
            }

            $activeYearId = AcademicYear::where('is_active', true)->value('id');

            return $user->hasStructuralRole('kepsek', $activeYearId)
                || $user->hasStructuralRole('waka_kurikulum', $activeYearId)
                || $user->assignments()
                    ->where('role_type', 'wali_kelas')
                    ->where('classroom_id', $course->classroom_id)
                    ->exists();
        }

        return $course->status === 'active'
            && $course->students()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'active')
                ->exists();
    }

    public function manage(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru()
            && $course->teachers()->where('users.id', $user->id)->exists();
    }
}
