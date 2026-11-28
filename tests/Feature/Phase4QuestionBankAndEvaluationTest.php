<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4QuestionBankAndEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_question_bank_import_to_quiz_and_view_analytics(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 14,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 140,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 3',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'siap_id' => 1400,
            'code' => 'TLJ',
            'name' => 'Teknologi Layanan Jaringan',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'TLJ XI TKJ 3',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subject->id,
                'category' => 'VoIP',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Protokol pensinyalan VoIP adalah?',
                'option_a' => 'SIP',
                'option_b' => 'FTP',
                'correct_answer' => 'A',
                'points' => 100,
            ])
            ->assertRedirect();

        $bankItem = QuestionBank::first();
        $this->assertNotNull($bankItem);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Evaluasi VoIP',
            'duration_minutes' => 20,
            'max_attempts' => 1,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'passing_score' => 75,
            'quiz_type' => 'remedial',
            'is_published' => true,
        ]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.import-bank', [$course, $quiz]), [
                '_token' => 'test-token',
                'question_bank_ids' => [$bankItem->id],
            ])
            ->assertRedirect();

        $this->assertEquals(1, $quiz->questions()->count());

        $this->actingAs($teacher)
            ->get(route('courses.quizzes.show', [$course, $quiz]))
            ->assertOk()
            ->assertSee('Rata-rata Nilai')
            ->assertSee('Perlu Remedial');
    }
}
