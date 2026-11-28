<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3AttendanceAndProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_session_student_can_check_in_and_view_progress(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 13,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 130,
            'academic_year_id' => $year->id,
            'name' => 'X TKJ 1',
            'grade_level' => 10,
        ]);

        $subject = Subject::create([
            'siap_id' => 1300,
            'code' => 'KJD',
            'name' => 'Komputer dan Jaringan Dasar',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'KJD X TKJ 1',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        CourseTeacher::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'is_primary' => true,
        ]);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.sessions.store', $course), [
                '_token' => 'test-token',
                'title' => 'Pertemuan 1: Perakitan PC',
                'topic' => 'Komponen Motherboard',
                'session_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $session = $course->learningSessions()->first();
        $this->assertNotNull($session);

        $this->actingAs($student)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.sessions.check-in', [$course, $session]), [
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_attendances', [
            'learning_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $this->actingAs($student)
            ->get(route('progress.index'))
            ->assertOk()
            ->assertSee('Progres &amp; Riwayat Pembelajaran', false)
            ->assertSee($course->title);
    }
}
