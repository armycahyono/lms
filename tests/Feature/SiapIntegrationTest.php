<?php

namespace Tests\Feature;

use App\Jobs\ProcessSiapWebhookJob;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\StudentClassHistory;
use App\Models\SyncLog;
use App\Models\User;
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
        $this->assertTrue($siswa->must_change_password);
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
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'username' => '0081234567',
            'password' => '0081234567',
        ])->assertRedirect('/ganti-password');

        $this->actingAs($siswa)
            ->get('/dashboard')
            ->assertRedirect('/ganti-password');

        $this->actingAs($siswa)
            ->post('/ganti-password', [
                'password' => 'RahasiaBaru123',
                'password_confirmation' => 'RahasiaBaru123',
            ])
            ->assertRedirect('/dashboard');

        $this->assertFalse($siswa->fresh()->must_change_password);
    }
}
