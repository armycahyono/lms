<?php

namespace Tests\Feature;

use App\Jobs\ProcessSiapWebhookJob;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\SyncCheckpoint;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\UserAssignment;
use App\Models\UserProfile;
use App\Services\SiapSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SiapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.siap.url' => 'http://siap.test',
            'services.siap.token' => 'test-secret-token',
        ]);
    }

    public function test_siap_sync_service_syncs_master_data_idempotently(): void
    {
        Http::fake([
            'http://siap.test/api/integration/lms/academic-years*' => Http::response([
                'data' => [
                    ['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true],
                ],
            ]),
            'http://siap.test/api/integration/lms/subjects*' => Http::response([
                'data' => [
                    ['siap_id' => 10, 'code' => 'PBO', 'name' => 'Pemrograman Berorientasi Objek'],
                ],
            ]),
            'http://siap.test/api/integration/lms/classrooms*' => Http::response([
                'data' => [
                    ['siap_id' => 20, 'academic_year_id' => 1, 'name' => 'X RPL 1', 'grade_level' => 'X', 'department' => 'RPL'],
                ],
            ]),
            'http://siap.test/api/integration/lms/teachers*' => Http::response([
                'data' => [
                    [
                        'siap_user_id' => 100,
                        'username' => '198001012010011001',
                        'name' => 'Budi Santoso',
                        'email' => 'budi@smk.test',
                        'nip' => '198001012010011001',
                        'phone' => '08123456789',
                        'base_role' => 'guru',
                        'is_active' => true,
                        'assignments' => [
                            ['role_type' => 'wali_kelas', 'classroom_siap_id' => 20],
                        ],
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/teaching-assignments*' => Http::response([
                'data' => [
                    [
                        'siap_assignment_id' => 500,
                        'teacher_siap_user_id' => 100,
                        'subject_siap_id' => 10,
                        'classroom_siap_id' => 20,
                        'academic_year_siap_id' => 1,
                        'academic_year' => '2026/2027',
                        'semester' => 'Ganjil',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/students*' => Http::response([
                'data' => [
                    [
                        'siap_student_id' => 300,
                        'nisn' => '0081234567',
                        'nis' => '12345',
                        'name' => 'Ani Lestari',
                        'email' => null,
                        'phone' => '08987654321',
                        'classroom_siap_id' => 20,
                        'status' => 'aktif',
                        'is_active' => true,
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/attendances*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/subject-attendances*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/visitations*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/incidents*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/permits*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/bk-records*' => Http::response(['data' => []]),
            'http://siap.test/api/integration/lms/calendar-events*' => Http::response(['data' => []]),
        ]);

        $summary = app(SiapSyncService::class)->syncAll();

        $this->assertSame(1, $summary['academic_years']);
        $this->assertSame(1, $summary['subjects']);
        $this->assertSame(1, $summary['classrooms']);
        $this->assertSame(1, $summary['teachers']);
        $this->assertSame(1, $summary['teaching_assignments']);
        $this->assertSame(1, $summary['students']);

        $guru = User::where('siap_user_id', 100)->first();
        $this->assertNotNull($guru);
        $this->assertTrue($guru->hasStructuralRole('wali_kelas'));

        $siswa = User::where('username', '0081234567')->first();
        $this->assertNotNull($siswa);
        $this->assertFalse($siswa->must_change_password);
        $this->assertTrue(Hash::check('0081234567', $siswa->password));
        $this->assertCount(1, $siswa->classrooms);

        $this->assertSame(1, Course::count());
        $this->assertSame(1, SyncLog::where('status', 'success')->count());
    }

    public function test_webhook_requires_valid_token_and_queues_job(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/webhooks/siap', [
            'event' => 'student.updated',
            'payload' => ['nisn' => '0081234567', 'name' => 'Ani'],
        ])->assertUnauthorized();

        $this->withToken('test-secret-token')
            ->postJson('/api/v1/webhooks/siap', [
                'event' => 'student.updated',
                'payload' => ['nisn' => '0081234567', 'name' => 'Ani'],
            ])
            ->assertOk()
            ->assertJson(['status' => 'queued', 'event' => 'student.updated']);

        Queue::assertPushed(ProcessSiapWebhookJob::class);
    }

    public function test_student_mutation_webhook_job_updates_class_history(): void
    {
        $year = AcademicYear::create(['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classA = Classroom::create(['siap_id' => 20, 'academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        $classB = Classroom::create(['siap_id' => 21, 'academic_year_id' => $year->id, 'name' => 'X RPL 2']);

        $sync = app(SiapSyncService::class);
        $sync->upsertStudentFromPayload([
            'siap_student_id' => 300,
            'nisn' => '0081234567',
            'name' => 'Ani Lestari',
            'classroom_siap_id' => 20,
            'is_active' => true,
        ]);

        $job = new ProcessSiapWebhookJob('student.mutated', [
            'siap_student_id' => 300,
            'nisn' => '0081234567',
            'name' => 'Ani Lestari',
            'classroom_siap_id' => 21,
            'reason' => 'Pindah kelas',
            'is_active' => true,
        ]);
        $job->handle($sync);

        $siswa = User::where('username', '0081234567')->firstOrFail();
        $this->assertSame(2, StudentClassHistory::where('student_user_id', $siswa->id)->count());
        $this->assertNotNull(
            StudentClassHistory::where('student_user_id', $siswa->id)
                ->where('classroom_id', $classA->id)
                ->value('left_at')
        );
        $this->assertNull(
            StudentClassHistory::where('student_user_id', $siswa->id)
                ->where('classroom_id', $classB->id)
                ->value('left_at')
        );
    }

    public function test_sso_callback_authenticates_teacher_and_redirects_to_dashboard(): void
    {
        AcademicYear::create(['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);

        Http::fake([
            'http://siap.test/api/integration/lms/sso/verify' => Http::response([
                'siap_user_id' => 100,
                'username' => '198001012010011001',
                'name' => 'Budi Santoso',
                'email' => 'budi@smk.test',
                'nip' => '198001012010011001',
                'phone' => '08123456789',
                'base_role' => 'guru',
                'assignments' => [
                    ['role_type' => 'waka_kurikulum', 'classroom_siap_id' => null],
                ],
            ]),
        ]);

        $code = str_repeat('a', 64);

        $response = $this->get("/sso/callback?code={$code}");
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertSame('guru', $user->role);
        $this->assertTrue($user->hasStructuralRole('waka_kurikulum'));
    }

    public function test_student_must_change_password_on_first_login(): void
    {
        $siswa = User::factory()->siswa()->create([
            'username' => '0081234567',
            'password' => Hash::make('0081234567'),
            'must_change_password' => false,
        ]);

        $this->post('/login', [
            'username' => '0081234567',
            'password' => '0081234567',
        ])->assertRedirect('/dashboard');

        $this->actingAs($siswa)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($siswa)
            ->post('/ganti-password', [
                'password' => 'RahasiaBaru123',
                'password_confirmation' => 'RahasiaBaru123',
            ])
            ->assertRedirect('/dashboard');

        $this->assertFalse($siswa->fresh()->must_change_password);
    }

    public function test_staff_gtk_are_excluded_from_siap_sync_and_sso_login(): void
    {
        AcademicYear::create(['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);

        Http::fake([
            'http://siap.test/api/integration/lms/teachers*' => Http::response([
                'data' => [
                    [
                        'siap_user_id' => 201,
                        'username' => 'guru.aktif',
                        'name' => 'Guru Aktif',
                        'base_role' => 'guru',
                    ],
                    [
                        'siap_user_id' => 202,
                        'username' => 'staf.tu',
                        'name' => 'Staf Tata Usaha',
                        'base_role' => 'staff',
                    ],
                    [
                        'siap_user_id' => 203,
                        'username' => 'tendik.perpus',
                        'name' => 'Tendik Perpus',
                        'base_role' => 'guru',
                        'gtk_type' => 'tendik',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/sso/verify' => Http::response([
                'siap_user_id' => 202,
                'username' => 'staf.tu',
                'name' => 'Staf Tata Usaha',
                'base_role' => 'staff',
            ]),
        ]);

        $syncedCount = app(SiapSyncService::class)->syncTeachers();
        $this->assertSame(1, $syncedCount);
        $this->assertDatabaseHas('users', ['username' => 'guru.aktif']);
        $this->assertDatabaseMissing('users', ['username' => 'staf.tu']);
        $this->assertDatabaseMissing('users', ['username' => 'tendik.perpus']);

        $code = str_repeat('b', 64);
        $this->get("/sso/callback?code={$code}")
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_inactive_student_sync_deactivates_classroom_and_course_enrollments(): void
    {
        $year = AcademicYear::create(['siap_id' => 10, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 30, 'academic_year_id' => $year->id, 'name' => 'XI TKJ 1']);
        $subject = Subject::create(['siap_id' => 40, 'code' => 'PBO', 'name' => 'Pemrograman Berorientasi Objek']);
        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO - XI TKJ 1',
            'status' => 'active',
        ]);

        $sync = app(SiapSyncService::class);
        $student = $sync->upsertStudentFromPayload([
            'siap_student_id' => 401,
            'nisn' => '0081234568',
            'name' => 'Budi Pelajar',
            'classroom_siap_id' => 30,
            'status' => 'aktif',
            'is_active' => true,
        ]);

        $this->assertNotNull($student);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $sync->upsertStudentFromPayload([
            'siap_student_id' => 401,
            'nisn' => '0081234568',
            'name' => 'Budi Pelajar',
            'classroom_siap_id' => 30,
            'status' => 'mutasi',
            'is_active' => false,
        ]);

        $this->assertFalse($student->fresh()->is_active);
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'inactive',
        ]);
        $history = StudentClassHistory::where('student_user_id', $student->id)->firstOrFail();
        $this->assertNotNull($history->left_at);
        $this->assertSame('mutasi', $history->reason);

        $sync->upsertStudentFromPayload([
            'siap_student_id' => 401,
            'nisn' => '0081234568',
            'name' => 'Budi Pelajar',
            'classroom_siap_id' => 30,
            'status' => 'aktif',
            'is_active' => true,
        ]);

        $this->assertTrue($student->fresh()->is_active);
        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);
    }

    public function test_opening_course_skips_inactive_students(): void
    {
        $year = AcademicYear::create(['siap_id' => 11, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 31, 'academic_year_id' => $year->id, 'name' => 'XII RPL 1']);
        $subject = Subject::create(['siap_id' => 41, 'code' => 'WEB', 'name' => 'Pemrograman Web']);
        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'WEB - XII RPL 1',
            'status' => 'draft',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $activeStudent = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        $inactiveStudent = User::factory()->create(['role' => 'siswa', 'is_active' => false]);

        ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_user_id' => $activeStudent->id, 'status' => 'active']);
        ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_user_id' => $inactiveStudent->id, 'status' => 'active']);

        $this->actingAs($teacher)
            ->post(route('courses.open', $course))
            ->assertRedirect();

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $activeStudent->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $inactiveStudent->id,
        ]);
    }

    public function test_past_academic_year_course_retains_graduated_student_history(): void
    {
        $pastYear = AcademicYear::create(['siap_id' => 12, 'name' => '2024/2025', 'semester' => 'Genap', 'is_active' => false]);
        AcademicYear::create(['siap_id' => 13, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);

        $pastClassroom = Classroom::create(['siap_id' => 32, 'academic_year_id' => $pastYear->id, 'name' => 'XII IPA 1']);
        $subject = Subject::create(['siap_id' => 42, 'code' => 'MAT', 'name' => 'Matematika']);
        $pastCourse = Course::create([
            'academic_year_id' => $pastYear->id,
            'classroom_id' => $pastClassroom->id,
            'subject_id' => $subject->id,
            'title' => 'Matematika - XII IPA 1',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        CourseTeacher::create(['course_id' => $pastCourse->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $graduatedStudent = User::factory()->create([
            'role' => 'siswa',
            'name' => 'Alumni Berprestasi',
            'username' => '0089999999',
            'is_active' => true,
        ]);

        ClassroomStudent::create([
            'classroom_id' => $pastClassroom->id,
            'student_user_id' => $graduatedStudent->id,
            'status' => 'active',
        ]);
        CourseEnrollment::create([
            'course_id' => $pastCourse->id,
            'student_id' => $graduatedStudent->id,
            'enrolled_at' => now()->subYears(2),
            'status' => 'active',
        ]);

        app(SiapSyncService::class)->upsertStudentFromPayload([
            'siap_student_id' => 499,
            'nisn' => '0089999999',
            'name' => 'Alumni Berprestasi',
            'status' => 'lulus',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('classroom_students', [
            'classroom_id' => $pastClassroom->id,
            'student_user_id' => $graduatedStudent->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $pastCourse->id,
            'student_id' => $graduatedStudent->id,
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->get(route('courses.show', $pastCourse))
            ->assertOk()
            ->assertSee('Alumni Berprestasi');
    }

    public function test_admin_student_search_follows_selected_academic_year(): void
    {
        $pastYear = AcademicYear::create(['siap_id' => 14, 'name' => '2020/2021', 'semester' => 'Genap', 'is_active' => false]);
        $activeYear = AcademicYear::create(['siap_id' => 15, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);

        $pastClassroom = Classroom::create(['siap_id' => 33, 'academic_year_id' => $pastYear->id, 'name' => 'XII RPL 2020']);
        $activeClassroom = Classroom::create(['siap_id' => 34, 'academic_year_id' => $activeYear->id, 'name' => 'X RPL 2026']);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $alumni = User::factory()->create([
            'role' => 'siswa',
            'name' => 'Siswa Angkatan 2020',
            'username' => '0050002020',
            'is_active' => false,
        ]);
        ClassroomStudent::create([
            'classroom_id' => $pastClassroom->id,
            'student_user_id' => $alumni->id,
            'status' => 'active',
        ]);

        $currentStudent = User::factory()->create([
            'role' => 'siswa',
            'name' => 'Siswa Aktif 2026',
            'username' => '0080002026',
            'is_active' => true,
        ]);
        ClassroomStudent::create([
            'classroom_id' => $activeClassroom->id,
            'student_user_id' => $currentStudent->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['q' => 'Siswa']))
            ->assertOk()
            ->assertSee('Siswa Aktif 2026')
            ->assertDontSee('Siswa Angkatan 2020');

        $this->actingAs($admin)
            ->withSession(['selected_academic_year_id' => $pastYear->id])
            ->get(route('dashboard', ['q' => 'Siswa']))
            ->assertOk()
            ->assertSee('Siswa Angkatan 2020')
            ->assertDontSee('Siswa Aktif 2026');
    }

    public function test_teacher_sees_archive_mode_banner_when_viewing_inactive_academic_year(): void
    {
        $pastYear = AcademicYear::create(['siap_id' => 16, 'name' => '2020/2021', 'semester' => 'Genap', 'is_active' => false]);
        $activeYear = AcademicYear::create(['siap_id' => 17, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);

        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Mode Arsip: Anda sedang melihat data Tahun Ajaran');

        $this->actingAs($teacher)
            ->withSession(['selected_academic_year_id' => $pastYear->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Mode Arsip: Anda sedang melihat data Tahun Ajaran {$pastYear->name}")
            ->assertSee("Kembali ke Tahun Aktif ({$activeYear->name})");
    }

    public function test_admin_student_table_displays_wali_kelas_and_parent_contact(): void
    {
        $activeYear = AcademicYear::create(['siap_id' => 18, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 35, 'academic_year_id' => $activeYear->id, 'name' => 'XI RPL 2']);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $waliKelas = User::factory()->create(['role' => 'guru', 'name' => 'Pak Hendra Wali', 'is_active' => true]);

        UserAssignment::create([
            'user_id' => $waliKelas->id,
            'academic_year_id' => $activeYear->id,
            'role_type' => 'wali_kelas',
            'classroom_id' => $classroom->id,
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'name' => 'Rani Siswa',
            'username' => '0081112223',
            'is_active' => true,
        ]);
        ClassroomStudent::create([
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
            'status' => 'active',
        ]);

        $parent = User::factory()->create([
            'role' => 'orang_tua',
            'name' => 'Ibu Sari Ortu',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $parent->id,
            'nip_nisn' => $parent->username,
            'phone' => '081234567890',
        ]);
        $student->parents()->attach($parent->id, ['relationship' => 'ibu']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rani Siswa')
            ->assertSee('Pak Hendra Wali')
            ->assertSee('XI RPL 2')
            ->assertSee('Ibu Sari Ortu')
            ->assertSee('081234567890');

        $this->actingAs($waliKelas)
            ->get(route('directory.index', ['tab' => 'students']))
            ->assertOk()
            ->assertSee('Rani Siswa')
            ->assertSee('Pak Hendra Wali')
            ->assertSee('XI RPL 2')
            ->assertSee('Ibu Sari Ortu')
            ->assertSee('081234567890');
    }

    public function test_sync_creates_parent_account_ortu_nisn_and_syncs_attendances(): void
    {
        $year = AcademicYear::create(['siap_id' => 19, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 36, 'academic_year_id' => $year->id, 'name' => 'X TKJ 1']);
        $subject = Subject::create(['siap_id' => 43, 'code' => 'INF', 'name' => 'Informatika']);
        $teacher = User::factory()->create(['siap_user_id' => 190, 'role' => 'guru', 'is_active' => true]);

        Http::fake([
            'http://siap.test/api/integration/lms/students*' => Http::response([
                'data' => [
                    [
                        'siap_student_id' => 501,
                        'nisn' => '0085556667',
                        'name' => 'Dina Pelajar',
                        'father_name' => 'Bapak Ahmad',
                        'phone_parent' => '081399998888',
                        'classroom_siap_id' => 36,
                        'status' => 'aktif',
                        'is_active' => true,
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/attendances*' => Http::response([
                'data' => [
                    [
                        'siap_attendance_id' => 7001,
                        'siap_student_id' => 501,
                        'nisn' => '0085556667',
                        'academic_year_siap_id' => 19,
                        'attendance_date' => '2026-11-28',
                        'status' => 'terlambat',
                        'check_in_time' => '07:25:00',
                        'notes' => 'Mesin Absen',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/subject-attendances*' => Http::response([
                'data' => [
                    [
                        'siap_record_id' => 8001,
                        'siap_student_id' => 501,
                        'nisn' => '0085556667',
                        'classroom_siap_id' => 36,
                        'subject_siap_id' => 43,
                        'teacher_siap_user_id' => 190,
                        'academic_year' => '2026/2027',
                        'semester' => 'Ganjil',
                        'attendance_date' => '2026-11-28',
                        'start_period' => 1,
                        'end_period' => 2,
                        'status' => 'h',
                        'topic' => 'Pengenalan Jaringan',
                        'notes' => null,
                    ],
                ],
            ]),
        ]);

        $sync = app(SiapSyncService::class);
        $this->assertSame(1, $sync->syncStudents());
        $this->assertSame(1, $sync->syncSchoolAttendances());
        $this->assertSame(1, $sync->syncSubjectAttendances());

        $student = User::where('username', '0085556667')->firstOrFail();
        $parent = User::where('username', 'ortu.0085556667')->firstOrFail();

        $this->assertSame('orang_tua', $parent->role);
        $this->assertSame('Bapak Ahmad', $parent->name);
        $this->assertSame('081399998888', $parent->profile?->phone);
        $this->assertTrue($student->parents()->where('users.id', $parent->id)->exists());

        $this->assertDatabaseHas('school_attendances', [
            'siap_attendance_id' => 7001,
            'student_user_id' => $student->id,
            'status' => 'terlambat',
        ]);

        $this->assertDatabaseHas('student_subject_attendances', [
            'siap_record_id' => 8001,
            'student_user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'h',
        ]);
    }

    public function test_fase_2_syncs_student_guidance_records_and_renders_homeroom_and_parent_views(): void
    {
        $year = AcademicYear::create(['siap_id' => 20, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 37, 'academic_year_id' => $year->id, 'name' => 'XII TKJ 2']);

        $waliKelas = User::factory()->create(['role' => 'guru', 'name' => 'Bu Maya Wali', 'is_active' => true]);
        UserAssignment::create([
            'user_id' => $waliKelas->id,
            'academic_year_id' => $year->id,
            'role_type' => 'wali_kelas',
            'classroom_id' => $classroom->id,
        ]);

        Http::fake([
            'http://siap.test/api/integration/lms/students*' => Http::response([
                'data' => [
                    [
                        'siap_student_id' => 601,
                        'nisn' => '0087778889',
                        'name' => 'Fajar Siswa',
                        'father_name' => 'Pak Joko',
                        'phone_parent' => '081277776666',
                        'classroom_siap_id' => 37,
                        'status' => 'aktif',
                        'is_active' => true,
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/visitations*' => Http::response([
                'data' => [
                    [
                        'siap_visitation_id' => 9101,
                        'siap_student_id' => 601,
                        'nisn' => '0087778889',
                        'academic_year_siap_id' => 20,
                        'visit_type' => 'kunjungan_rumah',
                        'visit_type_label' => 'Kunjungan Rumah (Home Visit)',
                        'reason' => 'Konfirmasi ketidakhadiran 3 hari',
                        'officer_name' => 'Bu Maya Wali',
                        'planned_date' => '2026-11-25',
                        'executed_date' => '2026-11-26',
                        'status' => 'completed',
                        'result_summary' => 'Siswa sempat sakit demam, sudah membaik',
                        'follow_up_plan' => 'Pantau kehadiran minggu depan',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/incidents*' => Http::response([
                'data' => [
                    [
                        'siap_incident_id' => 9201,
                        'siap_student_id' => 601,
                        'nisn' => '0087778889',
                        'academic_year' => '2026/2027',
                        'incident_date' => '2026-11-20',
                        'category' => 'Keterlambatan',
                        'description' => 'Datang terlambat 20 menit',
                        'action_taken' => 'Pembinaan lisan oleh piket',
                        'status' => 'resolved',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/permits*' => Http::response([
                'data' => [
                    [
                        'siap_permit_id' => 9301,
                        'siap_student_id' => 601,
                        'nisn' => '0087778889',
                        'permit_date' => '2026-11-22',
                        'permit_type' => 'izin_keluar_sementara',
                        'permit_type_label' => 'Izin Keluar Sementara',
                        'reason' => 'Mengambil obat ke klinik',
                        'status' => 'approved',
                    ],
                ],
            ]),
            'http://siap.test/api/integration/lms/bk-records*' => Http::response([
                'data' => [
                    [
                        'record_type' => 'achievement',
                        'siap_record_id' => 9401,
                        'siap_student_id' => 601,
                        'nisn' => '0087778889',
                        'academic_year_siap_id' => 20,
                        'record_date' => '2026-11-15',
                        'category' => 'Akademik',
                        'level' => 'Tingkat Kota / Kabupaten',
                        'title' => 'Juara 2 LKS Jaringan',
                        'summary' => 'Meraih medali perak bidang IT Network',
                        'recommendation' => 'Dinas Pendidikan',
                    ],
                ],
            ]),
        ]);

        $sync = app(SiapSyncService::class);
        $this->assertSame(1, $sync->syncStudents());
        $this->assertSame(1, $sync->syncVisitations());
        $this->assertSame(1, $sync->syncIncidents());
        $this->assertSame(1, $sync->syncPermits());
        $this->assertSame(1, $sync->syncBkRecords());

        $student = User::where('username', '0087778889')->firstOrFail();
        $parent = User::where('username', 'ortu.0087778889')->firstOrFail();

        $this->actingAs($waliKelas)
            ->get(route('homeroom.index'))
            ->assertOk()
            ->assertSee('Fajar Siswa')
            ->assertSee('Pak Joko');

        $this->actingAs($waliKelas)
            ->get(route('homeroom.show', $student))
            ->assertOk()
            ->assertSee('Konfirmasi ketidakhadiran 3 hari')
            ->assertSee('Datang terlambat 20 menit')
            ->assertSee('Mengambil obat ke klinik')
            ->assertSee('Juara 2 LKS Jaringan');

        $this->actingAs($waliKelas)
            ->post(route('homeroom.follow-ups.store', $student), [
                'follow_up_date' => '2026-11-28',
                'action_type' => 'komunikasi_ortu',
                'status' => 'selesai',
                'notes' => 'Sudah menghubungi Pak Joko untuk memantau kesehatan anak.',
            ])
            ->assertRedirect();

        $this->actingAs($parent)
            ->get(route('parent.records'))
            ->assertOk()
            ->assertSee('Siswa sempat sakit demam, sudah membaik')
            ->assertSee('Datang terlambat 20 menit')
            ->assertSee('Juara 2 LKS Jaringan')
            ->assertSee('Sudah menghubungi Pak Joko untuk memantau kesehatan anak.');
    }

    public function test_syncs_calendar_events_and_displays_on_schedule(): void
    {
        AcademicYear::create(['siap_id' => 21, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        Http::fake([
            'http://siap.test/api/integration/lms/calendar-events*' => Http::response([
                'data' => [
                    [
                        'siap_event_id' => 9501,
                        'academic_year' => '2026/2027',
                        'semester' => 'Ganjil',
                        'title' => 'Penilaian Akhir Semester',
                        'category' => 'kegiatan_kurikulum',
                        'category_label' => 'Agenda Kurikulum',
                        'start_date' => '2026-11-28',
                        'end_date' => '2026-11-29',
                        'is_all_day' => true,
                        'is_holiday' => false,
                    ],
                ],
            ]),
        ]);

        $count = app(SiapSyncService::class)->syncCalendarEvents();
        $this->assertSame(1, $count);
        $this->assertDatabaseHas('school_calendar_events', [
            'siap_event_id' => 9501,
            'title' => 'Penilaian Akhir Semester',
        ]);

        $this->actingAs($teacher)
            ->get(route('schedule.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Penilaian Akhir Semester')
            ->assertSee('Agenda Kurikulum');
    }

    public function test_attendance_webhook_is_accepted_and_queued(): void
    {
        Queue::fake();

        $this->withToken('test-secret-token')
            ->postJson('/api/v1/webhooks/siap', [
                'event' => 'attendance.recorded',
                'payload' => ['nisn' => '0081234567', 'status' => 'hadir'],
            ])
            ->assertOk()
            ->assertJson(['status' => 'queued', 'event' => 'attendance.recorded']);

        Queue::assertPushed(ProcessSiapWebhookJob::class);
    }

    public function test_sync_groups_use_incremental_checkpoints_and_settings_page_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Data Master (Jarang Berubah)')
            ->assertSee('Siswa, Orang Tua & Kalender')
            ->assertSee('Presensi, Visitasi, Piket & BK');

        Http::fake([
            'http://siap.test/api/integration/lms/*' => Http::response(['data' => []]),
        ]);

        // First run: full (no checkpoint yet)
        $this->actingAs($admin)
            ->post(route('admin.settings.sync-group'), [
                'group' => 'transactional',
                'mode' => 'incremental',
            ])
            ->assertRedirect();

        $checkpoint = SyncCheckpoint::where('group_key', 'transactional')->firstOrFail();
        $this->assertSame('full', $checkpoint->last_sync_mode);
        $this->assertNotNull($checkpoint->last_synced_at);

        // Second run: incremental (uses updated_since)
        $this->actingAs($admin)
            ->post(route('admin.settings.sync-group'), [
                'group' => 'transactional',
                'mode' => 'incremental',
            ])
            ->assertRedirect();

        $this->assertSame('incremental', $checkpoint->fresh()->last_sync_mode);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'updated_since='));

        $this->actingAs($admin)
            ->post(route('admin.settings.checkpoints.toggle', $checkpoint))
            ->assertRedirect();

        $this->assertFalse($checkpoint->fresh()->auto_sync_enabled);

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('transactional.incremental');

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'connection']))
            ->assertOk()
            ->assertSee('attendance.recorded');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'scope=window'));

        $this->actingAs($admin)
            ->post(route('admin.settings.sync-group'), [
                'group' => 'all_years_full',
                'mode' => 'full',
            ])
            ->assertRedirect();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'scope=all'));

        $this->assertDatabaseHas('sync_logs', [
            'source' => 'siap_manual',
            'event_type' => 'manual.sync_all_years',
            'status' => 'success',
        ]);

        app(SiapSyncService::class)->resyncSchoolAttendances();

        $this->assertDatabaseHas('sync_logs', [
            'source' => 'siap_parent',
            'event_type' => 'parent.resync_attendance',
            'status' => 'success',
        ]);
    }
}
