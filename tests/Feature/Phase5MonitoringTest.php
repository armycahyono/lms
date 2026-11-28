<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Models\UserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepsek_and_admin_can_access_monitoring_while_student_is_forbidden(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 15,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $kepsek = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        UserAssignment::create([
            'user_id' => $kepsek->id,
            'academic_year_id' => $year->id,
            'role_type' => 'kepsek',
        ]);

        $this->actingAs($admin)->get(route('monitoring.index'))
            ->assertOk()
            ->assertSee('Monitoring Guru')
            ->assertSee('Monitoring Kelas')
            ->assertSee('Monitoring Siswa');

        $this->actingAs($kepsek)->get(route('monitoring.index'))
            ->assertOk()
            ->assertSee('Monitoring Pembelajaran');

        $this->actingAs($student)->get(route('monitoring.index'))
            ->assertForbidden();
    }
}
