<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\LmsNotification;
use App\Models\User;
use App\Models\UserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NavigationAndLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_student_can_access_global_navigation_pages(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => false,
        ]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lms-bottom-nav')
            ->assertSee('Kelas Digital')
            ->assertSee('Wali Kelas')
            ->assertSee($student->username);

        $this->actingAs($student)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Daftar Tugas');

        $this->actingAs($student)->get(route('quizzes.index'))
            ->assertOk()
            ->assertSee('Ujian Kelas di LMS');

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi');

        $this->actingAs($student)->get(route('profile.show'))
            ->assertOk()
            ->assertSee($student->name);
    }

    public function test_student_can_mark_notification_as_read(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => false,
        ]);

        $notification = LmsNotification::create([
            'user_id' => $student->id,
            'type' => 'assignment',
            'title' => 'Tugas Baru',
            'message' => 'Ada tugas baru di kelas Anda.',
        ]);

        $this->actingAs($student)
            ->withSession(['_token' => 'test-token'])
            ->post(route('notifications.read', $notification), ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_login_page_displays_direct_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Username / NISN')
            ->assertSee('Kata Sandi')
            ->assertSee('Masuk ke LMS')
            ->assertSee('Asah Keterampilan – Teguhkan Keimanan – Raih Kesuksesan');
    }

    public function test_design_preview_page_renders_all_three_concepts(): void
    {
        $this->get(route('design.preview'))
            ->assertOk()
            ->assertSee('1. Vibrant Tech (EdTech)')
            ->assertSee('2. Clean Bento + Dark Mode')
            ->assertSee('3. Playful Interactive');

        $this->get(route('header.preview'))
            ->assertOk()
            ->assertSee('Opsi 1: SVG Wave + Glow')
            ->assertSee('Opsi 2: Canvas Riak Air')
            ->assertSee('Opsi 3: Playful Tactile');
    }

    public function test_user_can_update_profile_card_background(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => false,
        ]);

        $this->actingAs($student)
            ->withSession(['_token' => 'test-token'])
            ->post(route('profile.identity.update'), [
                '_token' => 'test-token',
                'template' => 'custom',
                'photo_pos' => 'left',
                'photo_shape' => 'rounded',
                'accent_color' => '#0284c7',
                'bg_color' => '#0f172a',
                'animation' => 'wave',
                'avatar_scale' => 140,
                'avatar_pos_x' => 30,
                'avatar_pos_y' => 70,
            ])
            ->assertRedirect();

        $profile = $student->fresh()->profile;
        $this->assertEquals('custom', $profile?->card_settings['template'] ?? null);
        $this->assertEquals('left', $profile?->card_settings['photo_pos'] ?? null);
        $this->assertEquals('wave', $profile?->card_settings['animation'] ?? null);
        $this->assertEquals(140, $profile?->card_settings['avatar_scale'] ?? null);
    }

    public function test_student_is_not_forced_to_change_password(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => true,
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_teacher_can_login_directly_and_admin_can_reset_teacher_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create([
            'role' => 'guru',
            'username' => 'guru.test',
            'password' => bcrypt('custom-secret'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'username' => 'guru.test',
            'password' => 'custom-secret',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('logout'));

        $this->actingAs($admin)
            ->post(route('admin.teachers.reset-password', $teacher))
            ->assertRedirect();

        $teacher->refresh();
        $this->assertTrue(Hash::check('password', $teacher->password));
        $this->assertTrue((bool) $teacher->must_change_password);
    }

    public function test_admin_and_homeroom_teacher_can_reset_student_and_parent_passwords_while_other_teacher_is_forbidden(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 99,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 990,
            'academic_year_id' => $year->id,
            'name' => 'XI TKJ 2',
            'grade_level' => 11,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $waliKelas = User::factory()->create(['role' => 'guru']);
        $otherTeacher = User::factory()->create(['role' => 'guru']);

        UserAssignment::create([
            'user_id' => $waliKelas->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'role_type' => 'wali_kelas',
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'username' => '1234567890',
            'password' => bcrypt('custom-student-pass'),
            'must_change_password' => false,
        ]);

        ClassroomStudent::create([
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
            'status' => 'active',
        ]);

        $parent = User::factory()->create([
            'role' => 'orang_tua',
            'username' => 'ortu.1234567890',
            'password' => bcrypt('custom-parent-pass'),
            'must_change_password' => false,
        ]);

        $student->parents()->attach($parent->id, ['relationship' => 'ayah']);

        // Non-wali-kelas teacher is forbidden
        $this->actingAs($otherTeacher)
            ->post(route('admin.students.reset-password', $student))
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->post(route('admin.students.reset-parent-password', $student))
            ->assertForbidden();

        // Wali kelas can reset student and parent password to NISN
        $this->actingAs($waliKelas)
            ->post(route('admin.students.reset-password', $student))
            ->assertRedirect();

        $this->actingAs($waliKelas)
            ->post(route('admin.students.reset-parent-password', $student))
            ->assertRedirect();

        $this->assertTrue(Hash::check('1234567890', $student->fresh()->password));
        $this->assertTrue((bool) $student->fresh()->must_change_password);
        $this->assertTrue(Hash::check('1234567890', $parent->fresh()->password));
        $this->assertTrue((bool) $parent->fresh()->must_change_password);

        // Admin can also reset
        $parent->update(['password' => bcrypt('another-pass'), 'must_change_password' => false]);
        $this->actingAs($admin)
            ->post(route('admin.students.reset-parent-password', $student))
            ->assertRedirect();

        $this->assertTrue(Hash::check('1234567890', $parent->fresh()->password));
    }
}
