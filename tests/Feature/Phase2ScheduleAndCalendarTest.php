<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2ScheduleAndCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_schedule_and_student_sees_schedule_and_calendar_events(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 12,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 120,
            'academic_year_id' => $year->id,
            'name' => 'XII RPL 1',
            'grade_level' => 12,
        ]);

        $subject = Subject::create([
            'siap_id' => 1200,
            'code' => 'PPL',
            'name' => 'Pemodelan Perangkat Lunak',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PPL XII RPL 1',
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

        Assignment::create([
            'course_id' => $course->id,
            'title' => 'Diagram Use Case',
            'due_at' => now()->startOfMonth()->addDays(10),
            'max_score' => 100,
            'is_published' => true,
        ]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.schedules.store', $course), [
                '_token' => 'test-token',
                'day_of_week' => 1,
                'start_time' => '07:00',
                'end_time' => '08:30',
                'room' => 'Lab RPL 1',
            ])
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertSee('Pemodelan Perangkat Lunak')
            ->assertSee('Lab RPL 1')
            ->assertSee('Diagram Use Case');
    }
}
