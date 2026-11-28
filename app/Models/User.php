<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'siap_user_id',
    'username',
    'name',
    'email',
    'password',
    'role',
    'must_change_password',
    'is_active',
    'parent_notify_mode',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserAssignment::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_students', 'student_user_id', 'classroom_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function studentClassHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class, 'student_user_id');
    }

    public function scopeForAcademicYear(Builder $query, ?AcademicYear $academicYear): Builder
    {
        if (! $academicYear) {
            return $query;
        }

        if ($academicYear->is_active) {
            $query->where('is_active', true);
        }

        return $query->where(function (Builder $sub) use ($academicYear): void {
            $sub->whereHas('classrooms', function (Builder $cq) use ($academicYear): void {
                $cq->where('classrooms.academic_year_id', $academicYear->id);
                if ($academicYear->is_active) {
                    $cq->where('classroom_students.status', 'active');
                }
            })->orWhereHas('studentClassHistories', function (Builder $hq) use ($academicYear): void {
                $hq->where('academic_year_id', $academicYear->id);
                if ($academicYear->is_active) {
                    $hq->whereNull('left_at');
                }
            });
        });
    }

    public function teachingCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teachers', 'teacher_id', 'course_id')
            ->withPivot(['siap_assignment_id', 'is_primary'])
            ->withTimestamps();
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_enrollments', 'student_id', 'course_id')
            ->withPivot(['enrolled_at', 'status'])
            ->withTimestamps();
    }

    public function materialProgress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class, 'student_id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function lmsNotifications(): HasMany
    {
        return $this->hasMany(LmsNotification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function isOrangTua(): bool
    {
        return in_array($this->role, ['orang_tua', 'wali_murid'], true);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'parent_user_id', 'student_user_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'student_user_id', 'parent_user_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function schoolAttendances(): HasMany
    {
        return $this->hasMany(SchoolAttendance::class, 'student_user_id');
    }

    public function subjectAttendances(): HasMany
    {
        return $this->hasMany(StudentSubjectAttendance::class, 'student_user_id');
    }

    public function visitations(): HasMany
    {
        return $this->hasMany(StudentVisitation::class, 'student_user_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(StudentIncident::class, 'student_user_id');
    }

    public function permits(): HasMany
    {
        return $this->hasMany(StudentPermit::class, 'student_user_id');
    }

    public function bkRecords(): HasMany
    {
        return $this->hasMany(StudentBkRecord::class, 'student_user_id');
    }

    public function homeroomFollowUps(): HasMany
    {
        return $this->hasMany(HomeroomFollowUp::class, 'student_user_id');
    }

    public function hasStructuralRole(string $roleType, ?int $academicYearId = null): bool
    {
        return $this->assignments()
            ->where('role_type', $roleType)
            ->when($academicYearId !== null, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->exists();
    }
}
