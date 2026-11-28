<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLearningExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_redesigned_course_material_and_assignment_pages(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 1,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 10,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 1',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'siap_id' => 100,
            'code' => 'PKK',
            'name' => 'Produk Kreatif',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Produk Kreatif XI TKJ 1',
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

        $material = Material::create([
            'course_id' => $course->id,
            'title' => 'Pengenalan Prototype',
            'type' => 'text',
            'content' => 'Materi pengenalan prototype produk.',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => 'Tugas Analisis Produk',
            'instructions' => 'Buat ringkasan analisis produk.',
            'max_score' => 100,
            'is_published' => true,
        ]);

        $this->actingAs($student)->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Progres Materi')
            ->assertSee($course->title);

        $this->actingAs($student)->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Progres Belajar')
            ->assertSee($material->title)
            ->assertSee($assignment->title);

        $this->actingAs($student)->get(route('courses.materials.show', [$course, $material]))
            ->assertOk()
            ->assertSee('Tandai Selesai')
            ->assertSee($material->title);

        $this->actingAs($student)->get(route('courses.assignments.show', [$course, $assignment]))
            ->assertOk()
            ->assertSee('Pengumpulan Jawaban')
            ->assertSee($assignment->title);
    }
}
