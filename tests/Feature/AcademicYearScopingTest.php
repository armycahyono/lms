<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_switch_academic_year_in_header_to_inspect_previous_year_data(): void
    {
        $oldYear = AcademicYear::create([
            'siap_id' => 30,
            'name' => '2025/2026',
            'semester' => 'genap',
            'is_active' => false,
        ]);

        $activeYear = AcademicYear::create([
            'siap_id' => 31,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $oldClass = Classroom::create(['siap_id' => 301, 'academic_year_id' => $oldYear->id, 'name' => 'X TKJ 1', 'grade_level' => 10]);
        $newClass = Classroom::create(['siap_id' => 302, 'academic_year_id' => $activeYear->id, 'name' => 'XI TKJ 1', 'grade_level' => 11]);
        $subject = Subject::create(['siap_id' => 3001, 'code' => 'AIJ', 'name' => 'AIJ']);

        $oldCourse = Course::create(['academic_year_id' => $oldYear->id, 'classroom_id' => $oldClass->id, 'subject_id' => $subject->id, 'title' => 'AIJ Tahun Lama', 'status' => 'active']);
        $newCourse = Course::create(['academic_year_id' => $activeYear->id, 'classroom_id' => $newClass->id, 'subject_id' => $subject->id, 'title' => 'AIJ Tahun Aktif', 'status' => 'active']);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        CourseTeacher::create(['course_id' => $oldCourse->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseTeacher::create(['course_id' => $newCourse->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        // Default: sees only active year course
        $this->actingAs($teacher)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('AIJ Tahun Aktif')
            ->assertDontSee('AIJ Tahun Lama');

        // Switch header academic year to previous year
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('academic-year.switch'), [
                '_token' => 'test-token',
                'academic_year_id' => $oldYear->id,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('AIJ Tahun Lama')
            ->assertDontSee('AIJ Tahun Aktif');
    }
}
