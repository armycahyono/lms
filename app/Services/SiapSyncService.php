<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\UserAssignment;
use App\Models\UserProfile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SiapSyncService
{
    public function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.siap.url', 'http://siap.test'), '/'))
            ->withToken((string) config('services.siap.token'))
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * @return array<string, int>
     */
    public function syncAll(?string $updatedSince = null): array
    {
        $summary = [
            'academic_years' => 0,
            'subjects' => 0,
            'classrooms' => 0,
            'teachers' => 0,
            'teaching_assignments' => 0,
            'students' => 0,
        ];

        try {
            $summary['academic_years'] = $this->syncAcademicYears($updatedSince);
            $summary['subjects'] = $this->syncSubjects($updatedSince);
            $summary['classrooms'] = $this->syncClassrooms($updatedSince);
            $summary['teachers'] = $this->syncTeachers($updatedSince);
            $summary['teaching_assignments'] = $this->syncTeachingAssignments($updatedSince);
            $summary['students'] = $this->syncStudents($updatedSince);

            SyncLog::create([
                'source' => 'siap',
                'event_type' => $updatedSince ? 'incremental_sync' : 'initial_sync',
                'status' => 'success',
                'summary_json' => $summary,
                'created_at' => now(),
            ]);

            return $summary;
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => 'siap',
                'event_type' => $updatedSince ? 'incremental_sync' : 'initial_sync',
                'status' => 'failed',
                'summary_json' => $summary,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    public function syncAcademicYears(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/academic-years', $updatedSince);

        return DB::transaction(function () use ($items): int {
            $count = 0;
            foreach ($items as $item) {
                AcademicYear::updateOrCreate(
                    ['siap_id' => $item['siap_id']],
                    [
                        'name' => $item['name'],
                        'semester' => $item['semester'] ?? 'Ganjil',
                        'is_active' => (bool) ($item['is_active'] ?? false),
                    ]
                );
                $count++;
            }

            return $count;
        });
    }

    public function syncSubjects(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/subjects', $updatedSince);

        return DB::transaction(function () use ($items): int {
            $count = 0;
            foreach ($items as $item) {
                Subject::withTrashed()->updateOrCreate(
                    ['siap_id' => $item['siap_id']],
                    [
                        'code' => $item['code'] ?? null,
                        'name' => $item['name'],
                        'deleted_at' => null,
                    ]
                );
                $count++;
            }

            return $count;
        });
    }

    public function syncClassrooms(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/classrooms', $updatedSince);
        $yearsBySiapId = AcademicYear::pluck('id', 'siap_id');

        return DB::transaction(function () use ($items, $yearsBySiapId): int {
            $count = 0;
            foreach ($items as $item) {
                $academicYearId = $yearsBySiapId[$item['academic_year_id']] ?? null;
                if (! $academicYearId) {
                    continue;
                }

                Classroom::withTrashed()->updateOrCreate(
                    ['siap_id' => $item['siap_id']],
                    [
                        'academic_year_id' => $academicYearId,
                        'name' => $item['name'],
                        'grade_level' => $item['grade_level'] ?? null,
                        'department' => $item['department'] ?? null,
                        'deleted_at' => null,
                    ]
                );
                $count++;
            }

            return $count;
        });
    }

    public function syncTeachers(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/teachers', $updatedSince);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $count = 0;
        foreach ($items as $item) {
            DB::transaction(function () use ($item, $activeYear): void {
                $this->upsertTeacherFromPayload($item, $activeYear);
            });
            $count++;
        }

        return $count;
    }

    public function syncTeachingAssignments(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/teaching-assignments', $updatedSince);

        return DB::transaction(function () use ($items): int {
            $count = 0;
            foreach ($items as $item) {
                if ($this->upsertTeachingAssignmentFromPayload($item)) {
                    $count++;
                }
            }

            return $count;
        });
    }

    public function syncStudents(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/students', $updatedSince);

        $count = 0;
        foreach ($items as $item) {
            $synced = DB::transaction(fn () => $this->upsertStudentFromPayload($item));
            if ($synced) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertTeacherFromPayload(array $item, ?AcademicYear $activeYear = null): User
    {
        $user = User::withTrashed()->where('siap_user_id', $item['siap_user_id'])->first();

        if ($user) {
            $user->update([
                'username' => $item['username'],
                'name' => $item['name'],
                'email' => $item['email'] ?? $user->email,
                'role' => $item['base_role'] ?? 'guru',
                'must_change_password' => false,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'deleted_at' => null,
            ]);
        } else {
            $user = User::create([
                'siap_user_id' => $item['siap_user_id'],
                'username' => $item['username'],
                'name' => $item['name'],
                'email' => $item['email'] ?? null,
                'password' => Hash::make(Str::random(32)),
                'role' => $item['base_role'] ?? 'guru',
                'must_change_password' => false,
                'is_active' => (bool) ($item['is_active'] ?? true),
            ]);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip_nisn' => $item['nip'] ?? $item['username'],
                'phone' => $item['phone'] ?? null,
            ]
        );

        if ($activeYear && isset($item['assignments']) && is_array($item['assignments'])) {
            $this->syncUserAssignments($user, $activeYear, $item['assignments']);
        }

        return $user;
    }

    /**
     * @param  array<int, array<string, mixed>>  $assignments
     */
    public function syncUserAssignments(User $user, AcademicYear $activeYear, array $assignments): void
    {
        UserAssignment::where('user_id', $user->id)
            ->where('academic_year_id', $activeYear->id)
            ->delete();

        $classroomsBySiapId = Classroom::pluck('id', 'siap_id');

        foreach ($assignments as $assignment) {
            $classroomId = null;
            if (! empty($assignment['classroom_siap_id'])) {
                $classroomId = $classroomsBySiapId[$assignment['classroom_siap_id']] ?? null;
            }

            UserAssignment::firstOrCreate([
                'user_id' => $user->id,
                'academic_year_id' => $activeYear->id,
                'role_type' => $assignment['role_type'],
                'classroom_id' => $classroomId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertTeachingAssignmentFromPayload(array $item): bool
    {
        $teacher = User::where('siap_user_id', $item['teacher_siap_user_id'])->first();
        $subject = Subject::where('siap_id', $item['subject_siap_id'])->first();
        $classroom = ! empty($item['classroom_siap_id'])
            ? Classroom::where('siap_id', $item['classroom_siap_id'])->first()
            : null;
        $academicYear = ! empty($item['academic_year_siap_id'])
            ? AcademicYear::where('siap_id', $item['academic_year_siap_id'])->first()
            : AcademicYear::where('name', $item['academic_year'] ?? '')->first();

        if (! $teacher || ! $subject || ! $classroom || ! $academicYear) {
            return false;
        }

        $course = Course::withTrashed()->firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
            ],
            [
                'title' => "{$subject->name} - {$classroom->name}",
                'status' => 'draft',
            ]
        );

        if ($course->trashed()) {
            $course->restore();
        }

        $hasPrimary = CourseTeacher::where('course_id', $course->id)
            ->where('is_primary', true)
            ->exists();

        CourseTeacher::updateOrCreate(
            [
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
            ],
            [
                'siap_assignment_id' => $item['siap_assignment_id'] ?? null,
                'is_primary' => ! $hasPrimary,
            ]
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertStudentFromPayload(array $item): ?User
    {
        $nisn = trim((string) ($item['nisn'] ?? ''));
        if ($nisn === '') {
            return null;
        }

        $existing = User::withTrashed()->where('username', $nisn)->first();
        $email = ! empty($item['email']) ? trim((string) $item['email']) : null;
        if ($email !== null && User::withTrashed()->where('email', $email)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            $email = null;
        }

        if ($existing) {
            $existing->update([
                'siap_user_id' => $item['siap_student_id'] ?? $existing->siap_user_id,
                'name' => $item['name'],
                'email' => $email ?? $existing->email,
                'role' => 'siswa',
                'is_active' => (bool) ($item['is_active'] ?? true),
                'deleted_at' => null,
            ]);
            $user = $existing;
        } else {
            $user = User::create([
                'siap_user_id' => $item['siap_student_id'] ?? null,
                'username' => $nisn,
                'name' => $item['name'],
                'email' => $email,
                'password' => Hash::make($nisn),
                'role' => 'siswa',
                'must_change_password' => true,
                'is_active' => (bool) ($item['is_active'] ?? true),
            ]);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip_nisn' => $nisn,
                'phone' => $item['phone'] ?? null,
            ]
        );

        if (! empty($item['classroom_siap_id'])) {
            $classroom = Classroom::where('siap_id', $item['classroom_siap_id'])->first();
            if ($classroom) {
                $previousActive = ClassroomStudent::where('student_user_id', $user->id)
                    ->where('status', 'active')
                    ->where('classroom_id', '!=', $classroom->id)
                    ->first();

                if ($previousActive) {
                    $previousActive->update(['status' => 'mutated']);
                    StudentClassHistory::where('student_user_id', $user->id)
                        ->where('classroom_id', $previousActive->classroom_id)
                        ->whereNull('left_at')
                        ->update(['left_at' => now(), 'reason' => $item['reason'] ?? 'Mutasi rombel']);
                }

                ClassroomStudent::updateOrCreate(
                    [
                        'classroom_id' => $classroom->id,
                        'student_user_id' => $user->id,
                    ],
                    ['status' => 'active']
                );

                StudentClassHistory::firstOrCreate(
                    [
                        'student_user_id' => $user->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $classroom->academic_year_id,
                        'left_at' => null,
                    ],
                    ['joined_at' => now()]
                );
            }
        }

        return $user;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchData(string $endpoint, ?string $updatedSince = null): array
    {
        $query = $updatedSince ? ['updated_since' => $updatedSince] : [];
        $response = $this->client()->get($endpoint, $query)->throw();

        return $response->json('data', []);
    }
}
