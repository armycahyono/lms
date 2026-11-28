<?php

namespace Tests\Feature;

use App\Jobs\ProcessSiapWebhookJob;
use App\Models\AcademicYear;
use App\Models\CbtExam;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UatEndToEndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_multi_subject_teachers_cbt_sync_and_parent_monitoring_flow(): void
    {
        Storage::fake('local');

        $year = AcademicYear::create([
            'siap_id' => 401,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 401,
            'academic_year_id' => $year->id,
            'name' => 'XI TKR 1',
            'grade_level' => 11,
            'department' => 'TKR',
        ]);

        $subBahasa = Subject::create(['siap_id' => 401, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $subMath = Subject::create(['siap_id' => 402, 'code' => 'MAT', 'name' => 'Matematika']);
        $subTkr = Subject::create(['siap_id' => 403, 'code' => 'TKR', 'name' => 'Pemeliharaan Mesin Kendaraan Ringan']);

        $guruBahasa = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $guruMath = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $guruKejuruan = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $siswa = User::factory()->create([
            'role' => 'siswa',
            'username' => '009111222',
            'siap_user_id' => 9111,
            'must_change_password' => false,
        ]);

        $orangTua = User::factory()->create(['role' => 'orang_tua', 'must_change_password' => false]);
        $orangTua->children()->attach($siswa->id, ['relationship' => 'ibu']);

        $courseTkr = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subTkr->id,
            'title' => 'PMKR XI TKR 1',
            'status' => 'active',
        ]);
        CourseTeacher::create(['course_id' => $courseTkr->id, 'teacher_id' => $guruKejuruan->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $courseTkr->id, 'student_id' => $siswa->id, 'enrolled_at' => now(), 'status' => 'active']);

        // 1. Guru Bahasa: buat soal teks bacaan berparagraf HTML langsung di Bank Soal
        $this->actingAs($guruBahasa)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subBahasa->id,
                'grade_level' => 11,
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => '<p>Paragraf 1: Keselamatan kerja adalah prioritas utama.</p><p>Paragraf 2: Gunakan alat sesuai standar.</p>',
                'option_a' => 'Keselamatan kerja',
                'option_b' => 'Kecepatan kerja',
                'correct_answer' => 'A',
                'points' => 20,
            ])
            ->assertRedirect();

        // 2. Guru Matematika: buat soal dengan rumus LaTeX & gambar opsi
        $this->actingAs($guruMath)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subMath->id,
                'grade_level' => 11,
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => '<p>Tentukan nilai $\frac{10}{2} + x^2$ untuk $x=3$.</p>',
                'option_a' => '14',
                'option_b' => '12',
                'option_image_a' => UploadedFile::fake()->image('opt_a.png', 50, 50),
                'correct_answer' => 'A',
                'points' => 25,
            ])
            ->assertRedirect();

        // 3. Guru Kejuruan (TKR): buat soal bergambar, filter jurusan, paketkan ke CBTPGRI, & uji Kuis Kelas
        $this->actingAs($guruKejuruan)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subTkr->id,
                'grade_level' => 11,
                'department' => 'TKR',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => '<p>Perhatikan posisi piston pada gambar berikut!</p>',
                'question_image' => UploadedFile::fake()->image('piston.png', 100, 100),
                'option_a' => 'TMA (Titik Mati Atas)',
                'option_b' => 'TMB (Titik Mati Bawah)',
                'correct_answer' => 'A',
                'points' => 100,
            ])
            ->assertRedirect();

        $qbTkr = QuestionBank::where('department', 'TKR')->firstOrFail();

        $this->actingAs($guruKejuruan)
            ->get(route('question-bank.index', ['department' => 'TKR']))
            ->assertOk()
            ->assertSee('Perhatikan posisi piston');

        $cbtExam = CbtExam::create([
            'course_id' => $courseTkr->id,
            'title' => 'PAS TKR XI',
            'exam_type' => 'PAS',
            'duration_minutes' => 90,
            'is_published' => true,
        ]);

        $this->actingAs($guruKejuruan)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.questions.attach', $cbtExam), [
                '_token' => 'test-token',
                'question_bank_ids' => [$qbTkr->id],
            ])
            ->assertRedirect();

        Http::fake([
            '*/api/v1/lms/sync-paket-soal' => Http::response([
                'success' => true,
                'id_bank' => 901,
                'bank_kode' => 'LMS-UAT-PAS',
                'questions_count' => 1,
            ], 200),
        ]);

        $this->actingAs($guruKejuruan)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.push-cbt', $cbtExam), ['_token' => 'test-token'])
            ->assertRedirect();

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return str_contains($request->url(), '/api/v1/lms/sync-paket-soal')
                && ! empty($data['questions'][0]['question_image']['base64']);
        });

        // 4. Siswa login pertama tanpa paksa ganti password -> ikut kuis kelas -> Orang Tua pantau presensi SIAP + nilai
        $quiz = Quiz::create([
            'course_id' => $courseTkr->id,
            'title' => 'UH 1 Sistem Bahan Bakar',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($guruKejuruan)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.import-bank', [$courseTkr, $quiz]), [
                '_token' => 'test-token',
                'question_bank_ids' => [$qbTkr->id],
            ])
            ->assertRedirect();

        $quizQuestion = $quiz->questions()->firstOrFail();

        $this->actingAs($siswa)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$courseTkr, $quiz]), ['_token' => 'test-token'])
            ->assertRedirect();

        $attempt = $quiz->attempts()->firstOrFail();

        $this->actingAs($siswa)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.attempts.submit', [$courseTkr, $quiz, $attempt]), [
                '_token' => 'test-token',
                'answers' => [$quizQuestion->id => 'A'],
            ])
            ->assertRedirect();

        ProcessSiapWebhookJob::dispatchSync('attendance.recorded', [
            'siap_attendance_id' => 9901,
            'siap_student_id' => 9111,
            'academic_year_siap_id' => 401,
            'attendance_date' => now()->toDateString(),
            'status' => 'hadir',
            'check_in_time' => '06:48:00',
        ]);

        $this->actingAs($orangTua)
            ->get(route('parent.index'))
            ->assertOk()
            ->assertSee('Hadir')
            ->assertSee('UH 1 Sistem Bahan Bakar')
            ->assertSee('100');
    }
}
