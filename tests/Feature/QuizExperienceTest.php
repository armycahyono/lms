<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\QuestionBank;
use App\Models\QuestionItemHistory;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_start_navigate_and_submit_exam_ui(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 2,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 20,
            'academic_year_id' => $year->id,
            'name' => 'XI RPL 1',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'siap_id' => 200,
            'code' => 'PBO',
            'name' => 'Pemrograman Berorientasi Objek',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO XI RPL 1',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => false,
        ]);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Ujian Harian 1',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'is_published' => true,
        ]);

        $q1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'multiple_choice',
            'question_text' => 'Apa kepanjangan OOP?',
            'options_json' => [
                'A' => 'Object Oriented Programming',
                'B' => 'Open Object Protocol',
            ],
            'correct_answer' => 'A',
            'points' => 50,
            'sort_order' => 1,
        ]);

        $startResponse = $this->actingAs($student)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$course, $quiz]), ['_token' => 'test-token']);

        $startResponse->assertRedirect();
        $attemptUrl = $startResponse->headers->get('Location');

        $this->actingAs($student)
            ->get($attemptUrl)
            ->assertOk()
            ->assertSee('Navigasi Nomor Soal')
            ->assertSee('Konfirmasi Pengumpulan Kuis')
            ->assertSee('exam-timer');

        $attempt = $quiz->attempts()->first();

        $this->actingAs($student)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.attempts.submit', [$course, $quiz, $attempt]), [
                '_token' => 'test-token',
                'answers' => [$q1->id => 'A'],
            ])
            ->assertRedirect(route('courses.quizzes.show', [$course, $quiz]));

        $this->assertEquals(50.0, (float) $attempt->fresh()->score);
        $this->assertEquals('graded', $attempt->fresh()->status);
    }

    public function test_autosave_focus_loss_multikey_short_answer_grade_by_question_and_permanent_item_analysis(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 21,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 21,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 1',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'siap_id' => 201,
            'code' => 'SEJ',
            'name' => 'Sejarah Indonesia',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Sejarah XI TKJ 1',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $studentPassed = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);
        $studentRemedial = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $studentPassed->id, 'enrolled_at' => now(), 'status' => 'active']);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $studentRemedial->id, 'enrolled_at' => now(), 'status' => 'active']);

        // Bank Soal with permanent tracking
        $qbShort = QuestionBank::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'difficulty' => 'mudah',
            'type' => 'short_answer',
            'question_text' => 'Siapa proklamator RI pertama?',
            'correct_answer' => 'Soekarno|Ir. Soekarno|Bung Karno',
            'points' => 40,
        ]);

        $qbEssay = QuestionBank::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'difficulty' => 'sulit',
            'type' => 'essay',
            'question_text' => 'Jelaskan makna proklamasi!',
            'points' => 60,
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'UH Sejarah Bab 1',
            'duration_minutes' => 45,
            'max_attempts' => 1,
            'passing_score' => 75,
            'is_published' => true,
        ]);

        // Import from bank -> increments usage_count & links question_bank_id
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.import-bank', [$course, $quiz]), [
                '_token' => 'test-token',
                'question_bank_ids' => [$qbShort->id, $qbEssay->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, $qbShort->fresh()->usage_count);
        $qShort = QuizQuestion::where('quiz_id', $quiz->id)->where('question_bank_id', $qbShort->id)->firstOrFail();
        $qEssay = QuizQuestion::where('quiz_id', $quiz->id)->where('question_bank_id', $qbEssay->id)->firstOrFail();
        $qEssay->update(['rubric_keywords' => 'kemerdekaan, berdaulat']);

        // Student 1 starts quiz, autosaves with focus_loss_count, then submits multi-key synonym
        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$course, $quiz]), ['_token' => 'test-token'])
            ->assertRedirect();

        $attempt1 = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $studentPassed->id)->firstOrFail();

        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->postJson(route('courses.quizzes.attempts.autosave', [$course, $quiz, $attempt1]), [
                '_token' => 'test-token',
                'answers' => [$qShort->id => 'ir. soekarno', $qEssay->id => 'Menandai kemerdekaan bangsa yang berdaulat.'],
                'focus_loss_count' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('focus_loss_count', 2);

        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.attempts.submit', [$course, $quiz, $attempt1]), [
                '_token' => 'test-token',
                'answers' => [$qShort->id => 'ir. soekarno', $qEssay->id => 'Menandai kemerdekaan bangsa yang berdaulat.'],
                'focus_loss_count' => 3,
            ])
            ->assertRedirect();

        $attempt1->refresh();
        $this->assertSame(40.0, (float) $attempt1->score);
        $this->assertSame('submitted', $attempt1->status);
        $this->assertSame(3, $attempt1->focus_loss_count);

        // Teacher grades essay via Grade-by-Question with keyword highlight
        $this->actingAs($teacher)
            ->get(route('courses.quizzes.questions.grade', [$course, $quiz, $qEssay]))
            ->assertOk()
            ->assertSee('kemerdekaan');

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.questions.grade.store', [$course, $quiz, $qEssay]), [
                '_token' => 'test-token',
                'scores' => [$attempt1->id => 50],
            ])
            ->assertRedirect();

        $attempt1->refresh();
        $this->assertSame(90.0, (float) $attempt1->score);
        $this->assertSame('graded', $attempt1->status);

        // Permanent Item Analysis check
        $qbShort->refresh();
        $this->assertSame(1, $qbShort->total_answered);
        $this->assertSame(1, $qbShort->total_correct);
        $this->assertSame(1, QuestionItemHistory::where('question_bank_id', $qbShort->id)->count());

        $this->actingAs($teacher)
            ->get(route('question-bank.analysis', $qbShort))
            ->assertOk()
            ->assertSee('UH Sejarah Bab 1')
            ->assertSee('100%');

        // Student 2 scored below KKTP (0) on parent quiz
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $studentRemedial->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'score' => 30,
            'status' => 'graded',
        ]);

        // Remedial quiz targeting only students < KKTP
        $remedialQuiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Remedial UH Sejarah',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 75,
            'quiz_type' => 'remedial',
            'target_remedial_only' => true,
            'parent_quiz_id' => $quiz->id,
            'is_published' => true,
        ]);

        // Student who already passed (>=75) is blocked
        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$course, $remedialQuiz]), ['_token' => 'test-token'])
            ->assertSessionHasErrors('quiz');

        // Student below KKTP (<75) can start remedial quiz
        $this->actingAs($studentRemedial)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$course, $remedialQuiz]), ['_token' => 'test-token'])
            ->assertRedirect();

        // Random Pool Quiz: 4 questions in pool, display_question_count = 2
        $poolQuiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'UH Random Pool',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'shuffle_questions' => true,
            'display_question_count' => 2,
            'is_published' => true,
        ]);

        foreach (range(1, 4) as $num) {
            QuizQuestion::create([
                'quiz_id' => $poolQuiz->id,
                'type' => 'multiple_choice',
                'question_text' => "Soal Pool #{$num}",
                'options_json' => ['A' => 'Benar', 'B' => 'Salah'],
                'correct_answer' => 'A',
                'points' => 50,
                'sort_order' => $num,
            ]);
        }

        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.start', [$course, $poolQuiz]), ['_token' => 'test-token'])
            ->assertRedirect();

        $poolAttempt = QuizAttempt::where('quiz_id', $poolQuiz->id)->where('student_id', $studentPassed->id)->firstOrFail();

        $this->actingAs($studentPassed)
            ->get(route('courses.quizzes.attempts.show', [$course, $poolQuiz, $poolAttempt]))
            ->assertOk()
            ->assertSee('Soal 1 dari 2');

        $poolAttempt->refresh();
        $this->assertCount(2, $poolAttempt->question_order_json);

        // Submit answers only for the 2 randomly selected questions -> score 100
        $answers = [];
        foreach ($poolAttempt->question_order_json as $qid) {
            $answers[$qid] = 'A';
        }

        $this->actingAs($studentPassed)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.quizzes.attempts.submit', [$course, $poolQuiz, $poolAttempt]), [
                '_token' => 'test-token',
                'answers' => $answers,
            ])
            ->assertRedirect();

        $this->assertSame(100.0, (float) $poolAttempt->fresh()->score);
    }
}
