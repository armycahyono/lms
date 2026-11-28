<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_learning_oriented_dashboard(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'must_change_password' => false,
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Progres Belajar:')
            ->assertSee('Tugas yang Harus Dikerjakan')
            ->assertSee('Jadwal Kuis')
            ->assertSee('Pengumuman Sekolah');
    }

    public function test_teacher_sees_teaching_summary_dashboard(): void
    {
        $teacher = User::factory()->create([
            'role' => 'guru',
            'must_change_password' => false,
        ]);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Diampu')
            ->assertSee('Tugas Belum Dinilai')
            ->assertSee('Kuis Berlangsung')
            ->assertSee('Aktivitas Siswa Terbaru');
    }

    public function test_admin_sees_system_summary_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Administrator')
            ->assertSee('Integrasi SIAP:')
            ->assertSee('Riwayat Sinkronisasi SIAP')
            ->assertSee('Sinkronkan Sekarang');
    }
}
