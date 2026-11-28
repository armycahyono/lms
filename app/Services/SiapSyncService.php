<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\SchoolAttendance;
use App\Models\SchoolCalendarEvent;
use App\Models\StudentBkRecord;
use App\Models\StudentClassHistory;
use App\Models\StudentIncident;
use App\Models\StudentPermit;
use App\Models\StudentSubjectAttendance;
use App\Models\StudentVisitation;
use App\Models\Subject;
use App\Models\SyncCheckpoint;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\UserAssignment;
use App\Models\UserProfile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SiapSyncService
{
    private string $activeScope = 'window';

    public const SYNC_GROUPS = [
        'master' => [
            'label' => 'Data Master (Jarang Berubah)',
            'description' => 'Tahun ajaran, mata pelajaran, kelas/rombel, guru, dan penugasan mengajar.',
            'default_minutes' => 1440,
            'entities' => ['academic_years', 'subjects', 'classrooms', 'teachers', 'teaching_assignments'],
        ],
        'students_calendar' => [
            'label' => 'Siswa, Orang Tua & Kalender',
            'description' => 'Data siswa, akun orang tua (ortu.nisn), mutasi rombel, dan kalender kegiatan sekolah.',
            'default_minutes' => 360,
            'entities' => ['students', 'calendar_events'],
        ],
        'transactional' => [
            'label' => 'Presensi, Visitasi, Piket & BK',
            'description' => 'Presensi harian, presensi mapel, visitasi rumah, insiden/izin piket, dan catatan BK non-rahasia.',
            'default_minutes' => 30,
            'entities' => ['school_attendances', 'subject_attendances', 'visitations', 'incidents', 'permits', 'bk_records'],
        ],
    ];

    /**
     * @return Collection<int, SyncCheckpoint>
     */
    public function ensureCheckpointsExist(): Collection
    {
        foreach (self::SYNC_GROUPS as $key => $meta) {
            SyncCheckpoint::firstOrCreate(
                ['group_key' => $key],
                [
                    'label' => $meta['label'],
                    'auto_sync_enabled' => true,
                    'schedule_minutes' => $meta['default_minutes'],
                ]
            );
        }

        return SyncCheckpoint::whereIn('group_key', array_keys(self::SYNC_GROUPS))
            ->get()
            ->sortBy(fn (SyncCheckpoint $cp) => array_search($cp->group_key, array_keys(self::SYNC_GROUPS), true))
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function syncGroup(string $group, bool $full = false, string $source = 'siap', string $scope = 'window'): array
    {
        if (! array_key_exists($group, self::SYNC_GROUPS)) {
            throw new \InvalidArgumentException("Kelompok sinkron '{$group}' tidak dikenali.");
        }

        $meta = self::SYNC_GROUPS[$group];
        $checkpoint = SyncCheckpoint::firstOrCreate(
            ['group_key' => $group],
            [
                'label' => $meta['label'],
                'auto_sync_enabled' => true,
                'schedule_minutes' => $meta['default_minutes'],
            ]
        );

        $updatedSince = (! $full && $checkpoint->last_synced_at)
            ? $checkpoint->last_synced_at->toIso8601String()
            : null;
        $mode = $updatedSince !== null ? 'incremental' : 'full';
        $startedAt = now();
        $summary = [];

        foreach ($meta['entities'] as $entity) {
            $summary[$entity] = 0;
        }

        $previousScope = $this->activeScope;
        $this->activeScope = $scope;

        try {
            foreach ($meta['entities'] as $entity) {
                $summary[$entity] = $this->syncEntity($entity, $updatedSince);
            }

            $checkpoint->update([
                'last_synced_at' => $startedAt,
                'last_sync_mode' => $mode,
                'last_summary_json' => $summary,
            ]);

            SyncLog::create([
                'source' => $source,
                'event_type' => "{$group}.{$mode}",
                'status' => 'success',
                'summary_json' => $summary,
                'created_at' => now(),
            ]);

            return $summary;
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => $source,
                'event_type' => "{$group}.{$mode}",
                'status' => 'failed',
                'summary_json' => $summary,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        } finally {
            $this->activeScope = $previousScope;
        }
    }

    private function syncEntity(string $entity, ?string $updatedSince): int
    {
        return match ($entity) {
            'academic_years' => $this->syncAcademicYears($updatedSince),
            'subjects' => $this->syncSubjects($updatedSince),
            'classrooms' => $this->syncClassrooms($updatedSince),
            'teachers' => $this->syncTeachers($updatedSince),
            'teaching_assignments' => $this->syncTeachingAssignments($updatedSince),
            'students' => $this->syncStudents($updatedSince),
            'calendar_events' => $this->syncCalendarEvents($updatedSince),
            'school_attendances' => $this->syncSchoolAttendances($updatedSince),
            'subject_attendances' => $this->syncSubjectAttendances($updatedSince),
            'visitations' => $this->syncVisitations($updatedSince),
            'incidents' => $this->syncIncidents($updatedSince),
            'permits' => $this->syncPermits($updatedSince),
            'bk_records' => $this->syncBkRecords($updatedSince),
            default => 0,
        };
    }

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
    public function syncAll(?string $updatedSince = null, string $scope = 'window', string $source = 'siap', ?string $eventType = null): array
    {
        $summary = [
            'academic_years' => 0,
            'subjects' => 0,
            'classrooms' => 0,
            'teachers' => 0,
            'teaching_assignments' => 0,
            'students' => 0,
            'school_attendances' => 0,
            'subject_attendances' => 0,
            'visitations' => 0,
            'incidents' => 0,
            'permits' => 0,
            'bk_records' => 0,
            'calendar_events' => 0,
        ];

        $resolvedEvent = $eventType ?? ($updatedSince ? 'incremental_sync' : 'initial_sync');
        $startedAt = now();
        $previousScope = $this->activeScope;
        $this->activeScope = $scope;

        try {
            $summary['academic_years'] = $this->syncAcademicYears($updatedSince);
            $summary['subjects'] = $this->syncSubjects($updatedSince);
            $summary['classrooms'] = $this->syncClassrooms($updatedSince);
            $summary['teachers'] = $this->syncTeachers($updatedSince);
            $summary['teaching_assignments'] = $this->syncTeachingAssignments($updatedSince);
            $summary['students'] = $this->syncStudents($updatedSince);
            $summary['school_attendances'] = $this->syncSchoolAttendances($updatedSince);
            $summary['subject_attendances'] = $this->syncSubjectAttendances($updatedSince);
            $summary['visitations'] = $this->syncVisitations($updatedSince);
            $summary['incidents'] = $this->syncIncidents($updatedSince);
            $summary['permits'] = $this->syncPermits($updatedSince);
            $summary['bk_records'] = $this->syncBkRecords($updatedSince);
            $summary['calendar_events'] = $this->syncCalendarEvents($updatedSince);

            $mode = $updatedSince !== null ? 'incremental' : 'full';
            foreach (self::SYNC_GROUPS as $groupKey => $meta) {
                $groupSummary = array_intersect_key($summary, array_flip($meta['entities']));
                SyncCheckpoint::updateOrCreate(
                    ['group_key' => $groupKey],
                    [
                        'label' => $meta['label'],
                        'last_synced_at' => $startedAt,
                        'last_sync_mode' => $mode,
                        'last_summary_json' => $groupSummary,
                    ]
                );
            }

            SyncLog::create([
                'source' => $source,
                'event_type' => $resolvedEvent,
                'status' => 'success',
                'summary_json' => $summary,
                'created_at' => now(),
            ]);

            return $summary;
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => $source,
                'event_type' => $resolvedEvent,
                'status' => 'failed',
                'summary_json' => $summary,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        } finally {
            $this->activeScope = $previousScope;
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
            $synced = DB::transaction(fn (): ?User => $this->upsertTeacherFromPayload($item, $activeYear));
            if ($synced !== null) {
                $count++;
            }
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
    public function isEligibleTeacherPayload(array $item): bool
    {
        $baseRole = strtolower(trim((string) ($item['base_role'] ?? '')));
        if ($baseRole !== '' && ! in_array($baseRole, ['guru', 'admin'], true)) {
            return false;
        }

        $gtkType = strtolower(trim((string) ($item['gtk_type'] ?? $item['jenis_gtk'] ?? $item['ptk_type'] ?? $item['type'] ?? '')));
        if ($gtkType !== '' && in_array($gtkType, ['staff', 'staf', 'tu', 'tendik', 'tenaga_kependidikan', 'pegawai', 'karyawan'], true)) {
            return false;
        }

        if (array_key_exists('is_teacher', $item) && ! (bool) $item['is_teacher'] && $baseRole !== 'admin') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertTeacherFromPayload(array $item, ?AcademicYear $activeYear = null): ?User
    {
        $user = User::withTrashed()->where('siap_user_id', $item['siap_user_id'])->first();

        if (! $this->isEligibleTeacherPayload($item)) {
            if ($user) {
                $user->update(['is_active' => false]);
            }

            return null;
        }

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
                'password' => Hash::make('password'),
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

        $isActive = (bool) ($item['is_active'] ?? true);

        if ($existing) {
            $existing->update([
                'siap_user_id' => $item['siap_student_id'] ?? $existing->siap_user_id,
                'name' => $item['name'],
                'email' => $email ?? $existing->email,
                'role' => 'siswa',
                'is_active' => $isActive,
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
                'must_change_password' => false,
                'is_active' => $isActive,
            ]);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip_nisn' => $nisn,
                'phone' => $item['phone'] ?? null,
            ]
        );

        $this->syncStudentParentFromPayload($user, $nisn, $item, $isActive);

        if (! $isActive) {
            $this->deactivateStudentMemberships(
                $user,
                $item['reason'] ?? ($item['status'] ?? 'Siswa tidak aktif')
            );

            return $user;
        }

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

                CourseEnrollment::where('student_id', $user->id)
                    ->where('status', 'inactive')
                    ->whereHas('course', fn ($q) => $q
                        ->where('classroom_id', $classroom->id)
                        ->where('status', 'active'))
                    ->update(['status' => 'active']);
            }
        }

        return $user;
    }

    private function deactivateStudentMemberships(User $user, mixed $reason): void
    {
        $resolvedReason = trim((string) $reason);
        if ($resolvedReason === '' || strtolower($resolvedReason) === 'aktif') {
            $resolvedReason = 'Siswa tidak aktif';
        }

        ClassroomStudent::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('classroom.academicYear', fn ($q) => $q->where('is_active', true))
            ->update(['status' => 'inactive']);

        StudentClassHistory::where('student_user_id', $user->id)
            ->whereNull('left_at')
            ->update([
                'left_at' => now(),
                'reason' => Str::limit($resolvedReason, 255, ''),
            ]);

        CourseEnrollment::where('student_id', $user->id)
            ->where('status', 'active')
            ->whereHas('course.academicYear', fn ($q) => $q->where('is_active', true))
            ->update(['status' => 'inactive']);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function syncStudentParentFromPayload(User $student, string $nisn, array $item, bool $isActive): void
    {
        $fatherName = trim((string) ($item['father_name'] ?? ''));
        $motherName = trim((string) ($item['mother_name'] ?? ''));
        $guardianName = trim((string) ($item['guardian_name'] ?? ''));

        $parentName = $fatherName !== ''
            ? $fatherName
            : ($motherName !== '' ? $motherName : $guardianName);

        $relationship = $fatherName !== ''
            ? 'ayah'
            : ($motherName !== '' ? 'ibu' : 'wali');

        $parentPhone = '';
        foreach (['phone_parent', 'father_phone', 'mother_phone', 'guardian_phone'] as $key) {
            $candidate = trim((string) ($item[$key] ?? ''));
            if ($candidate !== '') {
                $parentPhone = $candidate;
                break;
            }
        }

        if ($parentName === '' && $parentPhone === '') {
            return;
        }

        $parentUsername = "ortu.{$nisn}";
        $parentUser = User::withTrashed()->where('username', $parentUsername)->first();
        $resolvedName = $parentName !== '' ? $parentName : "Orang Tua {$student->name}";

        if ($parentUser) {
            $parentUser->update([
                'name' => $resolvedName,
                'role' => 'orang_tua',
                'is_active' => $isActive,
                'deleted_at' => null,
            ]);
        } else {
            $parentUser = User::create([
                'username' => $parentUsername,
                'name' => $resolvedName,
                'password' => Hash::make($nisn),
                'role' => 'orang_tua',
                'must_change_password' => false,
                'is_active' => $isActive,
            ]);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $parentUser->id],
            [
                'nip_nisn' => $nisn,
                'phone' => $parentPhone !== '' ? $parentPhone : null,
            ]
        );

        $student->parents()->syncWithoutDetaching([
            $parentUser->id => ['relationship' => $relationship],
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertSchoolAttendanceFromPayload(array $item): ?SchoolAttendance
    {
        $student = null;
        if (! empty($item['student_user_id'])) {
            $student = User::where('role', 'siswa')->find((int) $item['student_user_id']);
        }
        if (! $student && ! empty($item['siap_student_id'])) {
            $student = User::where('role', 'siswa')->where('siap_user_id', $item['siap_student_id'])->first();
        }
        if (! $student && ! empty($item['nisn'])) {
            $student = User::where('role', 'siswa')->where('username', trim((string) $item['nisn']))->first();
        }

        if (! $student) {
            return null;
        }

        $date = (string) ($item['attendance_date'] ?? now()->toDateString());
        $rawStatus = mb_strtolower(trim((string) ($item['status'] ?? 'hadir')));
        $status = array_key_exists($rawStatus, SchoolAttendance::STATUS_LABELS) ? $rawStatus : 'hadir';
        $checkInTime = ! empty($item['check_in_time']) ? (string) $item['check_in_time'] : null;
        $notes = isset($item['notes']) ? trim((string) $item['notes']) : null;
        $notes = $notes === '' ? null : $notes;
        $siapAttendanceId = ! empty($item['siap_attendance_id']) ? (int) $item['siap_attendance_id'] : null;

        $academicYear = null;
        if (! empty($item['academic_year_siap_id'])) {
            $academicYear = AcademicYear::where('siap_id', (int) $item['academic_year_siap_id'])->first();
        }
        $academicYear ??= AcademicYear::active();

        $existing = null;
        if ($siapAttendanceId !== null) {
            $existing = SchoolAttendance::where('siap_attendance_id', $siapAttendanceId)->first();
        }
        if (! $existing) {
            $existing = SchoolAttendance::where('student_user_id', $student->id)
                ->whereDate('attendance_date', $date)
                ->first();
        }

        $previousStatus = $existing?->status;
        $previousNotes = $existing?->notes;
        $hasMeaningfulChange = ! $existing
            || $previousStatus !== $status
            || trim((string) $previousNotes) !== trim((string) $notes);

        $record = SchoolAttendance::updateOrCreate(
            $siapAttendanceId !== null
                ? ['siap_attendance_id' => $siapAttendanceId]
                : ['student_user_id' => $student->id, 'attendance_date' => $date],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'attendance_date' => $date,
                'status' => $status,
                'check_in_time' => $checkInTime,
                'notes' => $notes,
                'siap_attendance_id' => $siapAttendanceId ?? $existing?->siap_attendance_id,
                'synced_at' => now(),
            ]
        );

        if ($hasMeaningfulChange) {
            $this->notifyParentsOfAttendance($student, $record, $previousStatus);
        }

        return $record;
    }

    public function syncSchoolAttendances(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/attendances', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertSchoolAttendanceFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    public function syncSubjectAttendances(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/subject-attendances', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertSubjectAttendanceFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertSubjectAttendanceFromPayload(array $item): ?StudentSubjectAttendance
    {
        $siapRecordId = (int) ($item['siap_record_id'] ?? 0);
        if ($siapRecordId <= 0) {
            return null;
        }

        $student = $this->resolveStudentFromPayload($item);
        if (! $student) {
            return null;
        }

        $classroom = ! empty($item['classroom_siap_id'])
            ? Classroom::where('siap_id', (int) $item['classroom_siap_id'])->first()
            : null;
        $subject = ! empty($item['subject_siap_id'])
            ? Subject::where('siap_id', (int) $item['subject_siap_id'])->first()
            : null;
        $teacher = ! empty($item['teacher_siap_user_id'])
            ? User::where('siap_user_id', (int) $item['teacher_siap_user_id'])->first()
            : null;

        $academicYear = $this->resolveAcademicYearFromPayload($item, $classroom?->academicYear);

        $rawStatus = strtolower(trim((string) ($item['status'] ?? 'h')));
        $status = array_key_exists($rawStatus, StudentSubjectAttendance::STATUS_LABELS) ? $rawStatus : 'h';

        return StudentSubjectAttendance::updateOrCreate(
            ['siap_record_id' => $siapRecordId],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'classroom_id' => $classroom?->id,
                'subject_id' => $subject?->id,
                'teacher_id' => $teacher?->id,
                'attendance_date' => (string) ($item['attendance_date'] ?? now()->toDateString()),
                'start_period' => isset($item['start_period']) ? (int) $item['start_period'] : null,
                'end_period' => isset($item['end_period']) ? (int) $item['end_period'] : null,
                'status' => $status,
                'topic' => isset($item['topic']) ? trim((string) $item['topic']) : null,
                'notes' => isset($item['notes']) ? trim((string) $item['notes']) : null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncVisitations(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/visitations', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertVisitationFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertVisitationFromPayload(array $item): ?StudentVisitation
    {
        $siapId = (int) ($item['siap_visitation_id'] ?? 0);
        $student = $this->resolveStudentFromPayload($item);
        if ($siapId <= 0 || ! $student) {
            return null;
        }

        $academicYear = $this->resolveAcademicYearFromPayload($item);

        return StudentVisitation::updateOrCreate(
            ['siap_visitation_id' => $siapId],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'visit_type' => $item['visit_type'] ?? null,
                'visit_type_label' => $item['visit_type_label'] ?? null,
                'reason' => $item['reason'] ?? null,
                'officer_name' => $item['officer_name'] ?? null,
                'planned_date' => $item['planned_date'] ?? null,
                'executed_date' => $item['executed_date'] ?? null,
                'status' => (string) ($item['status'] ?? 'planned'),
                'result_summary' => $item['result_summary'] ?? null,
                'follow_up_plan' => $item['follow_up_plan'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncIncidents(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/incidents', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertIncidentFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertIncidentFromPayload(array $item): ?StudentIncident
    {
        $siapId = (int) ($item['siap_incident_id'] ?? 0);
        $student = $this->resolveStudentFromPayload($item);
        if ($siapId <= 0 || ! $student) {
            return null;
        }

        $academicYear = $this->resolveAcademicYearFromPayload($item);

        return StudentIncident::updateOrCreate(
            ['siap_incident_id' => $siapId],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'incident_date' => $item['incident_date'] ?? null,
                'incident_time' => $item['incident_time'] ?? null,
                'location' => $item['location'] ?? null,
                'category' => $item['category'] ?? null,
                'description' => $item['description'] ?? null,
                'action_taken' => $item['action_taken'] ?? null,
                'follow_up_target' => $item['follow_up_target'] ?? null,
                'disciplinary_sanction' => $item['disciplinary_sanction'] ?? null,
                'parental_involvement' => (bool) ($item['parental_involvement'] ?? false),
                'parental_involvement_notes' => $item['parental_involvement_notes'] ?? null,
                'status' => $item['status'] ?? null,
                'recorder_name' => $item['recorder_name'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncPermits(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/permits', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertPermitFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertPermitFromPayload(array $item): ?StudentPermit
    {
        $siapId = (int) ($item['siap_permit_id'] ?? 0);
        $student = $this->resolveStudentFromPayload($item);
        if ($siapId <= 0 || ! $student) {
            return null;
        }

        $academicYear = $this->resolveAcademicYearFromPayload($item);

        return StudentPermit::updateOrCreate(
            ['siap_permit_id' => $siapId],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'permit_number' => $item['permit_number'] ?? null,
                'permit_date' => $item['permit_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'permit_type' => $item['permit_type'] ?? null,
                'permit_type_label' => $item['permit_type_label'] ?? null,
                'permit_category' => $item['permit_category'] ?? null,
                'reason' => $item['reason'] ?? null,
                'entry_time' => $item['entry_time'] ?? null,
                'departure_time' => $item['departure_time'] ?? null,
                'return_time' => $item['return_time'] ?? null,
                'action_taken' => $item['action_taken'] ?? null,
                'status' => $item['status'] ?? null,
                'giver_name' => $item['giver_name'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncBkRecords(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/bk-records', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertBkRecordFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertBkRecordFromPayload(array $item): ?StudentBkRecord
    {
        $recordType = trim((string) ($item['record_type'] ?? ''));
        $siapId = (int) ($item['siap_record_id'] ?? 0);
        $student = $this->resolveStudentFromPayload($item);

        if ($recordType === '' || $siapId <= 0 || ! $student) {
            return null;
        }

        $academicYear = $this->resolveAcademicYearFromPayload($item);

        return StudentBkRecord::updateOrCreate(
            [
                'record_type' => $recordType,
                'siap_record_id' => $siapId,
            ],
            [
                'academic_year_id' => $academicYear?->id,
                'student_user_id' => $student->id,
                'record_date' => $item['record_date'] ?? null,
                'category' => $item['category'] ?? null,
                'level' => $item['level'] ?? null,
                'title' => (string) ($item['title'] ?? 'Catatan BK'),
                'summary' => $item['summary'] ?? null,
                'recommendation' => $item['recommendation'] ?? null,
                'status' => $item['status'] ?? null,
                'recorded_by_name' => $item['recorded_by_name'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncCalendarEvents(?string $updatedSince = null): int
    {
        $items = $this->fetchData('/api/integration/lms/calendar-events', $updatedSince);
        $count = 0;

        foreach ($items as $item) {
            if (is_array($item) && $this->upsertCalendarEventFromPayload($item) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function upsertCalendarEventFromPayload(array $item): ?SchoolCalendarEvent
    {
        $siapId = (int) ($item['siap_event_id'] ?? 0);
        $title = trim((string) ($item['title'] ?? ''));
        $startDate = $item['start_date'] ?? null;

        if ($siapId <= 0 || $title === '' || empty($startDate)) {
            return null;
        }

        $academicYear = $this->resolveAcademicYearFromPayload($item);

        return SchoolCalendarEvent::updateOrCreate(
            ['siap_event_id' => $siapId],
            [
                'academic_year_id' => $academicYear?->id,
                'title' => $title,
                'description' => $item['description'] ?? null,
                'category' => $item['category'] ?? null,
                'category_label' => $item['category_label'] ?? null,
                'start_date' => $startDate,
                'end_date' => $item['end_date'] ?? $startDate,
                'start_time' => $item['start_time'] ?? null,
                'end_time' => $item['end_time'] ?? null,
                'is_all_day' => (bool) ($item['is_all_day'] ?? true),
                'is_holiday' => (bool) ($item['is_holiday'] ?? false),
                'color' => $item['color'] ?? null,
                'location' => $item['location'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveAcademicYearFromPayload(array $item, ?AcademicYear $fallback = null): ?AcademicYear
    {
        if (! empty($item['academic_year_siap_id'])) {
            $year = AcademicYear::where('siap_id', (int) $item['academic_year_siap_id'])->first();
            if ($year) {
                return $year;
            }
        }

        if (! empty($item['academic_year'])) {
            $year = AcademicYear::where('name', (string) $item['academic_year'])
                ->when(! empty($item['semester']), fn ($q) => $q->where('semester', (string) $item['semester']))
                ->first()
                ?? AcademicYear::where('name', (string) $item['academic_year'])->first();

            if ($year) {
                return $year;
            }
        }

        return $fallback ?? AcademicYear::active();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveStudentFromPayload(array $item): ?User
    {
        if (! empty($item['student_user_id'])) {
            $student = User::where('role', 'siswa')->find((int) $item['student_user_id']);
            if ($student) {
                return $student;
            }
        }
        if (! empty($item['siap_student_id'])) {
            $student = User::where('role', 'siswa')->where('siap_user_id', $item['siap_student_id'])->first();
            if ($student) {
                return $student;
            }
        }
        if (! empty($item['nisn'])) {
            return User::where('role', 'siswa')->where('username', trim((string) $item['nisn']))->first();
        }

        return null;
    }

    public function resyncSchoolAttendances(?string $date = null, ?string $nisn = null, string $source = 'siap_parent'): int
    {
        $query = array_filter([
            'date' => $date,
            'nisn' => $nisn,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $rows = $this->client()->get('/api/integration/lms/attendances', $query)->throw()->json('data', []);
            $count = 0;

            foreach ($rows as $row) {
                if (is_array($row) && $this->upsertSchoolAttendanceFromPayload($row) !== null) {
                    $count++;
                }
            }

            SyncLog::create([
                'source' => $source,
                'event_type' => 'parent.resync_attendance',
                'status' => 'success',
                'summary_json' => [
                    'school_attendances' => $count,
                    'nisn' => $nisn,
                ],
                'created_at' => now(),
            ]);

            return $count;
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => $source,
                'event_type' => 'parent.resync_attendance',
                'status' => 'failed',
                'summary_json' => ['nisn' => $nisn],
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    private function notifyParentsOfAttendance(User $student, SchoolAttendance $attendance, ?string $previousStatus): void
    {
        $parents = $student->parents()->get();
        if ($parents->isEmpty()) {
            return;
        }

        $notifications = app(LmsNotificationService::class);
        $statusLabel = $attendance->statusLabel();
        $dateLabel = $attendance->attendance_date->format('d M Y');
        $timeSuffix = $attendance->check_in_time ? " (Pukul {$attendance->check_in_time})" : '';
        $notesSuffix = $attendance->notes ? " — Catatan: {$attendance->notes}" : '';

        foreach ($parents as $parent) {
            $mode = $parent->parent_notify_mode ?: 'alerts_only';
            $shouldNotify = $mode === 'all'
                || $attendance->status !== 'hadir'
                || ($previousStatus !== null && $previousStatus !== $attendance->status);

            if (! $shouldNotify) {
                continue;
            }

            $notifications->notifyUser(
                $parent,
                'attendance',
                "Presensi Sekolah {$student->name}: {$statusLabel}",
                "{$student->name} tercatat {$statusLabel} pada {$dateLabel}{$timeSuffix}.{$notesSuffix}",
                [
                    'student_user_id' => $student->id,
                    'school_attendance_id' => $attendance->id,
                    'status' => $attendance->status,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchData(string $endpoint, ?string $updatedSince = null): array
    {
        $query = ['scope' => $this->activeScope];
        if ($updatedSince) {
            $query['updated_since'] = $updatedSince;
        }
        $response = $this->client()->get($endpoint, $query)->throw();

        return $response->json('data', []);
    }
}
