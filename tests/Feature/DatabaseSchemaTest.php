<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Discussion;
use App\Models\LmsNotification;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\UserAssignment;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_lms_schema_and_relationships_work(): void
    {
        $academicYear = AcademicYear::create([
            'siap_id' => 1,
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 10,
            'academic_year_id' => $academicYear->id,
            'name' => 'X RPL 1',
            'grade_level' => 'X',
            'department' => 'RPL',
        ]);

        $subject = Subject::create([
            'siap_id' => 20,
            'code' => 'PBO',
            'name' => 'Pemrograman Berorientasi Objek',
        ]);

        $guru = User::factory()->guru()->create(['siap_user_id' => 101, 'username' => '198001012010011001']);
        $siswa = User::factory()->siswa()->create(['siap_user_id' => 201, 'username' => '0081234567', 'email' => null, 'must_change_password' => true]);

        UserProfile::create([
            'user_id' => $siswa->id,
            'nip_nisn' => '0081234567',
        ]);

        UserAssignment::create([
            'user_id' => $guru->id,
            'academic_year_id' => $academicYear->id,
            'role_type' => 'wali_kelas',
            'classroom_id' => $classroom->id,
        ]);

        $classroom->students()->attach($siswa->id, ['status' => 'active']);

        $course = Course::create([
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO - X RPL 1',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $course->teachers()->attach($guru->id, ['siap_assignment_id' => 501, 'is_primary' => true]);
        $course->students()->attach($siswa->id, ['enrolled_at' => now(), 'status' => 'active']);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Bab 1: Pengenalan OOP',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $material = Material::create([
            'module_id' => $module->id,
            'course_id' => $course->id,
            'title' => 'Konsep Class dan Object',
            'type' => 'text',
            'content' => 'Materi pengenalan class dan object.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        MaterialProgress::create([
            'material_id' => $material->id,
            'student_id' => $siswa->id,
            'completed_at' => now(),
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Tugas 1: Membuat Class',
            'max_score' => 100,
            'is_published' => true,
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $siswa->id,
            'content' => 'class Siswa {}',
            'submitted_at' => now(),
            'score' => 95.5,
            'graded_at' => now(),
            'graded_by' => $guru->id,
            'is_validated_for_siap' => true,
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Kuis Harian 1',
            'duration_minutes' => 20,
            'max_attempts' => 1,
            'is_published' => true,
        ]);

        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'multiple_choice',
            'question_text' => 'Apa itu enkapsulasi?',
            'options_json' => ['A' => 'Pembungkusan data', 'B' => 'Pewarisan'],
            'correct_answer' => 'A',
            'points' => 10,
            'sort_order' => 1,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $siswa->id,
            'started_at' => now(),
            'submitted_at' => now(),
            'answers_json' => ['1' => 'A'],
            'score' => 10,
            'status' => 'submitted',
        ]);

        Discussion::create([
            'course_id' => $course->id,
            'discussable_type' => Material::class,
            'discussable_id' => $material->id,
            'user_id' => $siswa->id,
            'body' => 'Pak, izin bertanya mengenai constructor.',
        ]);

        LmsNotification::create([
            'user_id' => $siswa->id,
            'type' => 'assignment.graded',
            'title' => 'Tugas Dinilai',
            'message' => 'Tugas 1 Anda telah dinilai.',
        ]);

        ActivityLog::create([
            'user_id' => $guru->id,
            'action' => 'assignment.graded',
            'subject_type' => Assignment::class,
            'subject_id' => $assignment->id,
            'created_at' => now(),
        ]);

        SyncLog::create([
            'source' => 'siap',
            'event_type' => 'initial_sync',
            'status' => 'success',
            'summary_json' => ['students' => 1],
            'created_at' => now(),
        ]);

        $this->assertTrue($guru->isGuru());
        $this->assertTrue($guru->hasStructuralRole('wali_kelas', $academicYear->id));
        $this->assertTrue($siswa->isSiswa());
        $this->assertCount(1, $course->teachers);
        $this->assertCount(1, $course->students);
        $this->assertCount(1, $course->modules);
        $this->assertCount(1, $material->discussions);
        $this->assertCount(1, $assignment->submissions);
        $this->assertCount(1, $quiz->questions);
        $this->assertCount(1, $quiz->attempts);
    }
}
