<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CbtExam;
use App\Models\CbtExamResult;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase6CbtAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_manage_cbt_exam_results_and_export_report_csv(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 16,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 160,
            'academic_year_id' => $year->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 12,
        ]);

        $subject = Subject::create([
            'siap_id' => 1600,
            'code' => 'ASJ',
            'name' => 'Administrasi Sistem Jaringan',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'ASJ XII TKJ 1',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.store'), [
                '_token' => 'test-token',
                'course_id' => $course->id,
                'title' => 'PAS Ganjil ASJ',
                'exam_type' => 'PAS',
                'duration_minutes' => 90,
            ])
            ->assertRedirect();

        $exam = $course->cbtExams()->first();
        $this->assertNotNull($exam);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.results.store', $exam), [
                '_token' => 'test-token',
                'student_id' => $student->id,
                'score' => 88,
            ])
            ->assertRedirect();

        $this->actingAs($student)->get(route('exams.index'))
            ->assertOk()
            ->assertSee('PAS Ganjil ASJ')
            ->assertSee('88.00');

        $this->actingAs($teacher)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Laporan Pembelajaran');

        $this->actingAs($teacher)->get(route('reports.export.courses'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_cbt_pull_with_essay_sets_pending_status_and_teacher_can_finalize_grade(): void
    {
        config()->set('services.cbt.url', 'https://cbt.example.test');
        config()->set('services.cbt.token', 'secret-token');

        $year = AcademicYear::create([
            'siap_id' => 17,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'siap_id' => 170,
            'academic_year_id' => $year->id,
            'name' => 'XII RPL 1',
            'grade_level' => 12,
        ]);

        $subject = Subject::create([
            'siap_id' => 1700,
            'code' => 'PBO',
            'name' => 'Pemrograman Berorientasi Objek',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO XII RPL 1',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $otherTeacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'username' => '0089991111', 'must_change_password' => false]);

        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

        $exam = CbtExam::create([
            'course_id' => $course->id,
            'cbt_exam_code' => 'LMS-99-PAS',
            'title' => 'PAS PBO',
            'exam_type' => 'PAS',
            'duration_minutes' => 90,
            'is_published' => true,
        ]);

        Http::fake([
            'https://cbt.example.test/api/v1/lms/paket-soal/LMS-99-PAS/results' => Http::response([
                'results' => [
                    [
                        'lms_student_id' => $student->id,
                        'nisn' => '0089991111',
                        'username' => '0089991111',
                        'student_name' => $student->name,
                        'grading_status' => 'pending_essay',
                        'auto_score' => 60.00,
                        'answers' => [
                            [
                                'nomor_soal' => 1,
                                'type' => 'multiple_choice',
                                'student_answer' => 'A',
                                'is_correct' => true,
                                'max_points' => 60,
                                'earned_points' => 60,
                            ],
                            [
                                'nomor_soal' => 2,
                                'type' => 'essay',
                                'student_answer' => 'Enkapsulasi adalah penyembunyian data.',
                                'max_points' => 40,
                                'earned_points' => null,
                            ],
                        ],
                    ],
                    [
                        'nisn' => '9999999999',
                        'student_name' => 'Siswa Tidak Terdaftar',
                        'score' => 70,
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.pull-cbt', $exam), ['_token' => 'test-token'])
            ->assertRedirect();

        $result = CbtExamResult::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($result);
        $this->assertSame('menunggu_koreksi', $result->status);
        $this->assertEquals('60.00', $result->auto_score);
        $this->assertCount(2, $result->answers);

        $syncLog = SyncLog::latest('id')->first();
        $this->assertNotNull($syncLog);
        $this->assertSame('partial', $syncLog->status);
        $this->assertSame(1, $syncLog->summary_json['unmatched']);

        // Student sees pending essay status
        $this->actingAs($student)->get(route('exams.index'))
            ->assertOk()
            ->assertSee('Menunggu Koreksi Esai');

        $essayAnswer = $result->answers()->where('question_type', 'essay')->firstOrFail();

        // Unauthorized teacher cannot grade
        $this->actingAs($otherTeacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.results.grade', $result), [
                '_token' => 'test-token',
                'scores' => [$essayAnswer->id => 30],
            ])
            ->assertForbidden();

        // Score exceeding max_points fails validation
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.results.grade', $result), [
                '_token' => 'test-token',
                'scores' => [$essayAnswer->id => 50],
            ])
            ->assertSessionHasErrors("scores.{$essayAnswer->id}");

        // Valid grading finalizes result
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.results.grade', $result), [
                '_token' => 'test-token',
                'scores' => [$essayAnswer->id => 32.5],
                'feedback' => [$essayAnswer->id => 'Jawaban cukup tepat.'],
            ])
            ->assertRedirect();

        $result->refresh();
        $this->assertSame('selesai', $result->status);
        $this->assertEquals('32.50', $result->essay_score);
        $this->assertEquals('92.50', $result->score);
        $this->assertSame($teacher->id, $result->graded_by);
        $this->assertNotNull($result->graded_at);
    }

    public function test_teacher_can_edit_cbt_package_add_parallel_class_and_sync_questions(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 18,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $class1 = Classroom::create(['siap_id' => 180, 'academic_year_id' => $year->id, 'name' => 'X TKJ 1', 'grade_level' => 10]);
        $class2 = Classroom::create(['siap_id' => 181, 'academic_year_id' => $year->id, 'name' => 'X TKJ 2', 'grade_level' => 10]);
        $subject = Subject::create(['siap_id' => 1800, 'code' => 'INF', 'name' => 'Informatika']);

        $course1 = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class1->id, 'subject_id' => $subject->id, 'title' => 'Informatika X TKJ 1', 'status' => 'active']);
        $course2 = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class2->id, 'subject_id' => $subject->id, 'title' => 'Informatika X TKJ 2', 'status' => 'active']);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        CourseTeacher::create(['course_id' => $course1->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseTeacher::create(['course_id' => $course2->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $question = QuestionBank::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'grade_level' => 10,
            'type' => 'multiple_choice',
            'difficulty' => 'mudah',
            'question_text' => 'Apa itu CPU?',
            'options_json' => ['A' => 'Otak komputer', 'B' => 'Monitor'],
            'correct_answer' => 'A',
            'points' => 10,
        ]);

        $exam1 = CbtExam::create([
            'course_id' => $course1->id,
            'title' => 'PTS Informatika X',
            'exam_type' => 'PTS',
            'duration_minutes' => 60,
            'is_published' => true,
        ]);
        $exam1->questions()->attach($question->id, ['sort_order' => 1]);

        $this->actingAs($teacher)->get(route('exams.edit', $exam1))
            ->assertOk()
            ->assertSee('X TKJ 1')
            ->assertSee('X TKJ 2');

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->put(route('exams.update', $exam1), [
                '_token' => 'test-token',
                'title' => 'PTS Informatika X Revisi',
                'exam_type' => 'PAS',
                'duration_minutes' => 90,
                'display_question_count' => 1,
                'is_published' => '1',
                'course_ids' => [$course1->id, $course2->id],
            ])
            ->assertRedirect();

        $exam1->refresh();
        $exam2 = CbtExam::where('course_id', $course2->id)->first();
        $this->assertNotNull($exam2);
        $this->assertSame('PTS Informatika X Revisi', $exam1->title);
        $this->assertSame('PTS Informatika X Revisi', $exam2->title);
        $this->assertSame('PAS', $exam2->exam_type);
        $this->assertSame(90, $exam2->duration_minutes);
        $this->assertTrue($exam2->questions()->where('question_banks.id', $question->id)->exists());

        // Detaching question from exam1 also detaches from exam2
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('exams.questions.detach', [$exam1, $question]), ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertFalse($exam1->questions()->where('question_banks.id', $question->id)->exists());
        $this->assertFalse($exam2->questions()->where('question_banks.id', $question->id)->exists());

        // Deleting package removes both parallel exams when no results exist
        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('exams.destroy', $exam1), ['_token' => 'test-token'])
            ->assertRedirect(route('exams.index', ['tab' => 'cbt']));

        $this->assertDatabaseMissing('cbt_exams', ['id' => $exam1->id]);
        $this->assertDatabaseMissing('cbt_exams', ['id' => $exam2->id]);
    }

    public function test_cbt_schedule_sync_webhook_pull_and_student_schedule_status_display(): void
    {
        config()->set('services.cbt.url', 'https://cbt.example.test');
        config()->set('services.cbt.token', 'cbt-secret-123');

        $year = AcademicYear::create([
            'siap_id' => 19,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classroom = Classroom::create(['siap_id' => 190, 'academic_year_id' => $year->id, 'name' => 'XI TKJ 1', 'grade_level' => 11]);
        $subject = Subject::create(['siap_id' => 1900, 'code' => 'TLJ', 'name' => 'Teknologi Layanan Jaringan']);
        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'title' => 'TLJ XI TKJ 1', 'status' => 'active']);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

        $ongoingExam = CbtExam::create([
            'course_id' => $course->id,
            'cbt_exam_code' => 'LMS-200-PAS',
            'title' => 'PAS TLJ Berlangsung',
            'exam_type' => 'PAS',
            'duration_minutes' => 90,
            'is_published' => true,
        ]);

        $upcomingExam = CbtExam::create([
            'course_id' => $course->id,
            'cbt_exam_code' => 'LMS-201-PAS',
            'title' => 'PAS TLJ Akan Datang',
            'exam_type' => 'PAS',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHours(2),
            'duration_minutes' => 90,
            'is_published' => true,
        ]);

        // Invalid token rejected
        $this->postJson('/api/v1/integration/cbt/schedule-sync', [
            'bank_kode' => 'LMS-200-PAS',
        ])->assertUnauthorized();

        // Valid webhook updates schedule
        $this->withToken('cbt-secret-123')
            ->postJson('/api/v1/integration/cbt/schedule-sync', [
                'bank_kode' => 'LMS-200-PAS',
                'start_at' => now()->subMinutes(15)->toIso8601String(),
                'end_at' => now()->addMinutes(75)->toIso8601String(),
                'duration_minutes' => 90,
                'launch_url' => 'https://cbt.example.test/ujian/LMS-200-PAS',
                'session_name' => 'Sesi 1',
                'room_name' => 'Lab 2',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'updated_exams' => 1]);

        $ongoingExam->refresh();
        $this->assertSame('Sesi 1', $ongoingExam->session_name);
        $this->assertSame('Lab 2', $ongoingExam->room_name);

        // Student sees ongoing exam with launch URL and upcoming exam locked
        $this->actingAs($student)->get(route('exams.index', ['tab' => 'cbt']))
            ->assertOk()
            ->assertSee('Sedang Berlangsung')
            ->assertSee('Akan Datang')
            ->assertSee('Sesi 1')
            ->assertSee('Lab 2')
            ->assertSee('https://cbt.example.test/ujian/LMS-200-PAS');

        // Teacher can also pull schedule from CBTPGRI
        Http::fake([
            'https://cbt.example.test/api/v1/lms/paket-soal/LMS-201-PAS/schedule' => Http::response([
                'start_at' => now()->addDay()->toIso8601String(),
                'end_at' => now()->addDay()->addHours(2)->toIso8601String(),
                'duration_minutes' => 120,
                'session_name' => 'Sesi 2',
                'room_name' => 'Lab 3',
            ], 200),
        ]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.pull-schedule', $upcomingExam), ['_token' => 'test-token'])
            ->assertRedirect();

        $upcomingExam->refresh();
        $this->assertSame(120, $upcomingExam->duration_minutes);
        $this->assertSame('Sesi 2', $upcomingExam->session_name);
        $this->assertSame('Lab 3', $upcomingExam->room_name);
    }
}
