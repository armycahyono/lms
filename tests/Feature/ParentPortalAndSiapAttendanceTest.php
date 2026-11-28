<?php

namespace Tests\Feature;

use App\Jobs\ProcessSiapWebhookJob;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LmsNotification;
use App\Models\SchoolAttendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentPortalAndSiapAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_siap_attendance_webhook_is_idempotent_and_anti_spam_notifies_parents(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 301,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'username' => '009888777',
            'siap_user_id' => 5001,
            'must_change_password' => false,
        ]);

        $parentAlertsOnly = User::factory()->create([
            'role' => 'orang_tua',
            'parent_notify_mode' => 'alerts_only',
            'must_change_password' => false,
        ]);

        $parentAll = User::factory()->create([
            'role' => 'orang_tua',
            'parent_notify_mode' => 'all',
            'must_change_password' => false,
        ]);

        $parentAlertsOnly->children()->attach($student->id, ['relationship' => 'ibu']);
        $parentAll->children()->attach($student->id, ['relationship' => 'ayah']);

        // 1. Webhook attendance.recorded with status 'hadir'
        ProcessSiapWebhookJob::dispatchSync('attendance.recorded', [
            'siap_attendance_id' => 7001,
            'siap_student_id' => 5001,
            'academic_year_siap_id' => 301,
            'attendance_date' => '2026-11-27',
            'status' => 'hadir',
            'check_in_time' => '06:55:00',
        ]);

        $this->assertDatabaseHas('school_attendances', [
            'siap_attendance_id' => 7001,
            'student_user_id' => $student->id,
            'status' => 'hadir',
        ]);

        // alerts_only skips 'hadir', 'all' receives 1 notification
        $this->assertSame(0, LmsNotification::where('user_id', $parentAlertsOnly->id)->count());
        $this->assertSame(1, LmsNotification::where('user_id', $parentAll->id)->count());

        // 2. Duplicate webhook with identical status & notes -> anti-spam: NO new notification
        ProcessSiapWebhookJob::dispatchSync('attendance.updated', [
            'siap_attendance_id' => 7001,
            'siap_student_id' => 5001,
            'attendance_date' => '2026-11-27',
            'status' => 'hadir',
            'check_in_time' => '06:55:00',
        ]);

        $this->assertSame(1, SchoolAttendance::count());
        $this->assertSame(1, LmsNotification::where('user_id', $parentAll->id)->count());

        // 3. Status changes to 'terlambat' -> both parents notified
        ProcessSiapWebhookJob::dispatchSync('attendance.updated', [
            'siap_attendance_id' => 7001,
            'siap_student_id' => 5001,
            'attendance_date' => '2026-11-27',
            'status' => 'terlambat',
            'check_in_time' => '07:20:00',
            'notes' => 'Macet di jalan',
        ]);

        $this->assertSame(1, LmsNotification::where('user_id', $parentAlertsOnly->id)->count());
        $this->assertSame(2, LmsNotification::where('user_id', $parentAll->id)->count());
    }

    public function test_parent_portal_supports_multi_child_views_resync_and_role_isolation(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 302,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 302,
            'academic_year_id' => $year->id,
            'name' => 'X TKJ 1',
            'grade_level' => 10,
        ]);

        $subject = Subject::create([
            'siap_id' => 302,
            'code' => 'INF',
            'name' => 'Informatika',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Informatika X TKJ 1',
            'status' => 'active',
        ]);

        $child1 = User::factory()->create(['role' => 'siswa', 'name' => 'Anak Pertama', 'username' => '111001', 'must_change_password' => false]);
        $child2 = User::factory()->create(['role' => 'siswa', 'name' => 'Anak Kedua', 'username' => '111002', 'must_change_password' => false]);
        $parent = User::factory()->create(['role' => 'orang_tua', 'must_change_password' => false]);

        $parent->children()->attach($child1->id, ['relationship' => 'ayah']);
        $parent->children()->attach($child2->id, ['relationship' => 'ayah']);

        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $child1->id, 'enrolled_at' => now(), 'status' => 'active']);
        Assignment::create(['course_id' => $course->id, 'title' => 'Tugas Algoritma', 'due_at' => now()->addDays(2), 'is_published' => true]);
        Announcement::create(['user_id' => $parent->id, 'title' => 'Libur Nasional', 'body' => 'Sekolah libur.', 'academic_year_id' => $year->id]);

        SchoolAttendance::create([
            'academic_year_id' => $year->id,
            'student_user_id' => $child1->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'hadir',
            'check_in_time' => '06:50:00',
            'synced_at' => now(),
        ]);

        // Dashboard redirects parent to Portal Orang Tua
        $this->actingAs($parent)->get(route('dashboard'))->assertRedirect(route('parent.index'));

        // Beranda 2 blocks + multi-child switcher
        $this->actingAs($parent)
            ->get(route('parent.index'))
            ->assertOk()
            ->assertSee('A. Kehadiran di Sekolah (SIAP)')
            ->assertSee('B. Aktivitas Pembelajaran LMS')
            ->assertSee('Anak Pertama')
            ->assertSee('Anak Kedua');

        // Switch active child to child2
        $this->actingAs($parent)
            ->get(route('parent.index', ['child_id' => $child2->id]))
            ->assertOk()
            ->assertSessionHas('parent_active_child_id', $child2->id);

        // Resync via SIAP API
        Http::fake([
            '*/api/v1/lms/attendances*' => Http::response([
                'data' => [
                    [
                        'siap_attendance_id' => 8801,
                        'nisn' => '111002',
                        'attendance_date' => now()->toDateString(),
                        'status' => 'izin',
                        'notes' => 'Acara keluarga',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($parent)
            ->withSession(['_token' => 'test-token', 'parent_active_child_id' => $child2->id])
            ->post(route('parent.attendance.resync'), ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('school_attendances', [
            'student_user_id' => $child2->id,
            'status' => 'izin',
            'notes' => 'Acara keluarga',
        ]);

        // All parent pages accessible
        $this->actingAs($parent)->get(route('parent.attendance'))->assertOk()->assertSee('Izin');
        $this->actingAs($parent)->get(route('parent.learning', ['child_id' => $child1->id]))->assertOk()->assertSee('Tugas Algoritma');
        $this->actingAs($parent)->get(route('parent.communications'))->assertOk()->assertSee('Libur Nasional');
        $this->actingAs($parent)->get(route('parent.profile'))->assertOk()->assertSee('Daftar Anak Terhubung');

        // Update notification preference
        $this->actingAs($parent)
            ->withSession(['_token' => 'test-token'])
            ->post(route('parent.preferences.update'), [
                '_token' => 'test-token',
                'parent_notify_mode' => 'all',
            ])
            ->assertRedirect();

        $this->assertSame('all', $parent->fresh()->parent_notify_mode);

        // Role isolation: student cannot open /orang-tua, parent cannot open /bank-soal
        $this->actingAs($child1)->get(route('parent.index'))->assertForbidden();
        $this->actingAs($parent)->get(route('question-bank.index'))->assertForbidden();
    }
}
