<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use App\Models\UserAssignment;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_admin_can_view_teacher_roles_and_student_phones_while_student_is_forbidden(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 20,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 200,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 1',
            'grade_level' => 11,
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'name' => 'Pak Budi Guru', 'must_change_password' => false]);
        UserAssignment::create([
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'role_type' => 'wali_kelas',
        ]);

        $student = User::factory()->create(['role' => 'siswa', 'name' => 'Ani Siswa', 'must_change_password' => false]);
        UserProfile::create([
            'user_id' => $student->id,
            'phone' => '081234567890',
        ]);

        $this->actingAs($teacher)->get(route('directory.index', ['tab' => 'teachers']))
            ->assertOk()
            ->assertSee('Pak Budi Guru')
            ->assertSee('Wali kelas (XI TKJ 1)');

        $this->actingAs($teacher)->get(route('directory.index', ['tab' => 'students']))
            ->assertOk()
            ->assertSee('Ani Siswa')
            ->assertSee('081234567890');

        $this->actingAs($student)->get(route('directory.index'))
            ->assertForbidden();
    }
}
