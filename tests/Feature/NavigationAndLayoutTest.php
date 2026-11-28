<?php

namespace Tests\Feature;

use App\Models\LmsNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_login_page_displays_siap_sso_button(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk Sebagai Murid')
            ->assertSee('Masuk Sebagai Guru')
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
}
