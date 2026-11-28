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

class Phase2NotificationsAndAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_publishing_material_assignment_and_announcement_notifies_enrolled_students(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 11,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 110,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 2',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'siap_id' => 1100,
            'code' => 'AIJ',
            'name' => 'Administrasi Infrastruktur Jaringan',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'AIJ XI TKJ 2',
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
            ->post(route('courses.materials.store', $course), [
                '_token' => 'test-token',
                'title' => 'Konfigurasi VLAN',
                'type' => 'text',
                'content' => 'Materi VLAN.',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.assignments.store', $course), [
                '_token' => 'test-token',
                'title' => 'Laporan VLAN',
                'max_score' => 100,
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.announcements.store', $course), [
                '_token' => 'test-token',
                'title' => 'Praktikum Besok',
                'body' => 'Bawa kabel UTP masing-masing.',
                'is_pinned' => '1',
            ])
            ->assertRedirect();

        $this->assertEquals(3, $student->lmsNotifications()->count());

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Praktikum Besok');
    }

    public function test_admin_can_publish_school_announcement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('announcements.school.store'), [
                '_token' => 'test-token',
                'title' => 'Libur Hari Guru',
                'body' => 'Pembelajaran daring mandiri.',
            ])
            ->assertRedirect();

        $this->assertEquals(1, $student->lmsNotifications()->count());
        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Libur Hari Guru');
    }
}
