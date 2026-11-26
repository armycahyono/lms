<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3EvaluationAndApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.siap.token' => 'siap-integration-secret',
        ]);
    }

    private function seedActiveCourse(): array
    {
        $year = AcademicYear::create(['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 10, 'academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        $subject = Subject::create(['siap_id' => 20, 'code' => 'PBO', 'name' => 'PBO']);

        $guru = User::factory()->guru()->create(['siap_user_id' => 100, 'username' => 'guru1']);
        $siswa = User::factory()->siswa()->create([
            'siap_user_id' => 200,
            'username' => '0081234567',
            'password' => Hash::make('Rahasia123'),
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO - X RPL 1',
            'status' => 'active',
            'opened_at' => now(),
        ]);
        $course->teachers()->attach($guru->id, ['is_primary' => true]);
        $course->students()->attach($siswa->id, ['enrolled_at' => now(), 'status' => 'active']);

        return compact('year', 'classroom', 'subject', 'guru', 'siswa', 'course');
    }

    public function test_daily_quiz_auto_scoring_and_teacher_grading(): void
    {
        ['guru' => $guru, 'siswa' => $siswa, 'course' => $course] = $this->seedActiveCourse();

        $this->actingAs($guru)->post(route('courses.quizzes.store', $course), [
            'title' => 'Kuis Bab 1',
            'duration_minutes' => 15,
            'max_attempts' => 1,
            'is_published' => true,
        ])->assertRedirect();

        $quiz = Quiz::where('course_id', $course->id)->firstOrFail();

        $this->actingAs($guru)->post(route('courses.quizzes.questions.store', [$course, $quiz]), [
            'type' => 'multiple_choice',
            'question_text' => 'Ibu kota Indonesia?',
            'option_a' => 'Jakarta',
            'option_b' => 'Bandung',
            'correct_answer' => 'A',
            'points' => 10,
        ]);

        $this->actingAs($guru)->post(route('courses.quizzes.questions.store', [$course, $quiz]), [
            'type' => 'essay',
            'question_text' => 'Jelaskan OOP!',
            'points' => 20,
        ]);

        $mcQuestion = QuizQuestion::where('quiz_id', $quiz->id)->where('type', 'multiple_choice')->firstOrFail();
        $essayQuestion = QuizQuestion::where('quiz_id', $quiz->id)->where('type', 'essay')->firstOrFail();

        $this->actingAs($siswa)->post(route('courses.quizzes.start', [$course, $quiz]))->assertRedirect();
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $siswa->id)->firstOrFail();

        $this->actingAs($siswa)->post(route('courses.quizzes.attempts.submit', [$course, $quiz, $attempt]), [
            'answers' => [
                $mcQuestion->id => 'A',
                $essayQuestion->id => 'Pemrograman berbasis objek.',
            ],
        ])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('10.00', $attempt->score);
        $this->assertSame('submitted', $attempt->status);

        $this->actingAs($guru)->post(route('courses.quizzes.attempts.grade', [$course, $quiz, $attempt]), [
            'score' => 28,
        ])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('28.00', $attempt->score);
        $this->assertSame('graded', $attempt->status);
    }

    public function test_siap_recap_endpoints_return_validated_grades_and_activity_summary(): void
    {
        ['guru' => $guru, 'siswa' => $siswa, 'course' => $course] = $this->seedActiveCourse();

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => 'Tugas 1',
            'max_score' => 100,
            'is_published' => true,
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $siswa->id,
            'content' => 'Jawaban',
            'submitted_at' => now(),
            'score' => 88,
            'graded_at' => now(),
            'graded_by' => $guru->id,
            'is_validated_for_siap' => true,
        ]);

        $this->getJson('/api/v1/integration/siap/grade-recap')->assertUnauthorized();

        $this->withToken('siap-integration-secret')
            ->getJson('/api/v1/integration/siap/grade-recap')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nisn', '0081234567')
            ->assertJsonPath('data.0.score', 88);

        $this->withToken('siap-integration-secret')
            ->getJson('/api/v1/integration/siap/class-activity-summary')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.enrolled_students_count', 1)
            ->assertJsonPath('data.0.published_assignments_count', 1);
    }

    public function test_mobile_student_api_with_sanctum_and_signed_file_urls(): void
    {
        Storage::fake('local');
        ['siswa' => $siswa, 'course' => $course] = $this->seedActiveCourse();

        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Bab 1', 'sort_order' => 1, 'is_published' => true]);
        $filePath = UploadedFile::fake()->create('modul.pdf', 50, 'application/pdf')->store("materials/{$course->id}", 'local');
        $material = Material::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Modul PDF',
            'type' => 'file',
            'file_path' => $filePath,
            'is_published' => true,
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => 'Tugas Mobile',
            'max_score' => 100,
            'is_published' => true,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => '0081234567',
            'password' => 'Rahasia123',
            'device_name' => 'android-test',
        ])->assertOk();

        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $courseDetail = $this->withToken($token)
            ->getJson("/api/v1/courses/{$course->id}")
            ->assertOk();

        $signedUrl = $courseDetail->json('data.modules.0.materials.0.download_url');
        $this->assertNotEmpty($signedUrl);

        $this->get($signedUrl)->assertOk();
        $this->get("/api/v1/signed-files/materials/{$material->id}")->assertForbidden();

        $this->withToken($token)
            ->postJson("/api/v1/materials/{$material->id}/complete")
            ->assertOk()
            ->assertJson(['status' => 'completed']);

        $this->withToken($token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'content' => 'Dikirim dari Flutter',
            ])
            ->assertOk()
            ->assertJson(['status' => 'submitted']);
    }
}
