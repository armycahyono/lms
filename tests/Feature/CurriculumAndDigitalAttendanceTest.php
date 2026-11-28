<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CbtExam;
use App\Models\CbtExamResult;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\LearningAttendance;
use App\Models\LearningObjective;
use App\Models\LearningSession;
use App\Models\Material;
use App\Models\ProjectModule;
use App\Models\QuestionBank;
use App\Models\RemedialEnrichment;
use App\Models\Subject;
use App\Models\TeachingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class CurriculumAndDigitalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_manage_teaching_plan_and_publish_to_parallel_courses(): void
    {
        $year = AcademicYear::create(['siap_id' => 90, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 901, 'code' => 'INF', 'name' => 'Informatika']);
        $class1 = Classroom::create(['siap_id' => 911, 'academic_year_id' => $year->id, 'name' => 'X TKJ 1', 'grade_level' => 10]);
        $class2 = Classroom::create(['siap_id' => 912, 'academic_year_id' => $year->id, 'name' => 'X TKJ 2', 'grade_level' => 10]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $course1 = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class1->id, 'subject_id' => $subject->id, 'title' => 'Informatika X TKJ 1', 'status' => 'active']);
        $course2 = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class2->id, 'subject_id' => $subject->id, 'title' => 'Informatika X TKJ 2', 'status' => 'active']);

        CourseTeacher::create(['course_id' => $course1->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseTeacher::create(['course_id' => $course2->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.store'), [
                '_token' => 'test-token',
                'subject_grade' => "{$subject->id}:10",
            ])
            ->assertRedirect();

        $plan = TeachingPlan::firstOrFail();

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->put(route('teaching-plans.update', $plan), [
                '_token' => 'test-token',
                'title' => 'Perangkat Informatika X',
                'phase' => 'Fase E',
                'capaian_pembelajaran' => 'Peserta didik memahami algoritma.',
                'effective_weeks' => 36,
                'total_jp' => 144,
                'kktp_type' => 'interval',
                'kktp_min_score' => 75,
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.objectives.store', $plan), [
                '_token' => 'test-token',
                'code' => 'TP-1',
                'title' => 'Menjelaskan konsep algoritma',
                'jp_allocation' => 4,
            ])
            ->assertRedirect();

        $this->assertSame(1, LearningObjective::where('teaching_plan_id', $plan->id)->count());

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.publish-parallel', $plan), [
                '_token' => 'test-token',
                'course_ids' => [$course1->id, $course2->id],
                'module_title' => 'Bab 1 Algoritma',
                'material_title' => 'LKPD 1 Algoritma',
                'category' => 'lkpd',
                'type' => 'text',
                'content' => 'Kerjakan LKPD berikut.',
                'is_published' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, Material::where('course_id', $course1->id)->where('title', 'LKPD 1 Algoritma')->count());
        $this->assertSame(1, Material::where('course_id', $course2->id)->where('title', 'LKPD 1 Algoritma')->count());
    }

    public function test_student_opening_digital_class_triggers_automatic_attendance_and_teacher_can_record_remedial(): void
    {
        $year = AcademicYear::create(['siap_id' => 91, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 902, 'code' => 'MTK', 'name' => 'Matematika']);
        $class = Classroom::create(['siap_id' => 913, 'academic_year_id' => $year->id, 'name' => 'X TKJ 3', 'grade_level' => 10]);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class->id, 'subject_id' => $subject->id, 'title' => 'Matematika X TKJ 3', 'status' => 'active']);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.sessions.store', $course), [
                '_token' => 'test-token',
                'title' => 'Pertemuan 1',
                'topic' => 'Persamaan Linear',
                'journal_summary' => 'Membahas sistem persamaan linear dua variabel.',
                'session_date' => now()->format('Y-m-d'),
                'is_achieved' => 1,
            ])
            ->assertRedirect();

        $session = LearningSession::firstOrFail();

        $this->actingAs($student)->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Hadir Otomatis');

        $attendance = LearningAttendance::where('learning_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('hadir', $attendance->status);
        $this->assertSame('auto_digital', $attendance->source);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('courses.remedial-enrichments.store', $course), [
                '_token' => 'test-token',
                'student_id' => $student->id,
                'program_type' => 'remedial',
                'initial_score' => 60,
                'final_score' => 80,
                'activity_notes' => 'Bimbingan ulang & tugas perbaikan',
            ])
            ->assertRedirect();

        $this->assertSame(1, RemedialEnrichment::where('course_id', $course->id)->where('student_id', $student->id)->count());
    }

    public function test_teacher_can_create_p5_project_kisi_kisi_and_export_teaching_journals(): void
    {
        $year = AcademicYear::create(['siap_id' => 92, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 903, 'code' => 'P5', 'name' => 'Projek IPAS']);
        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('projects.store'), [
                '_token' => 'test-token',
                'title' => 'Pengolahan Sampah Organik',
                'theme' => 'Gaya Hidup Berkelanjutan',
                'phase' => 'Fase E',
                'dimensions' => 'Gotong Royong, Kreatif',
            ])
            ->assertRedirect();

        $this->assertSame(1, ProjectModule::where('title', 'Pengolahan Sampah Organik')->count());

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subject->id,
                'category' => 'Bab 1',
                'kisi_indicator' => 'Siswa mampu menjelaskan daur ulang',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Apa manfaat kompos?',
                'option_a' => 'Menyuburkan tanah',
                'option_b' => 'Mencemari air',
                'correct_answer' => 'A',
                'points' => 10,
            ])
            ->assertRedirect();

        $this->assertSame('Siswa mampu menjelaskan daur ulang', QuestionBank::firstOrFail()->kisi_indicator);

        $this->actingAs($teacher)->get(route('reports.export.journals'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_siap_string_grade_level_parses_to_proper_numeric_grade_on_teaching_plan_options(): void
    {
        $year = AcademicYear::create(['siap_id' => 93, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 904, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $class = Classroom::create(['siap_id' => 914, 'academic_year_id' => $year->id, 'name' => 'XI TKJ 1', 'grade_level' => 'Tingkat XI (Sebelas)']);
        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class->id, 'subject_id' => $subject->id, 'title' => 'Bahasa Indonesia XI TKJ 1', 'status' => 'active']);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);

        $this->actingAs($teacher)->get(route('teaching-plans.index'))
            ->assertOk()
            ->assertSee('Bahasa Indonesia — Kelas 11')
            ->assertDontSee('Kelas 0');
    }

    public function test_owner_authority_controls_sharing_and_copying_for_teaching_plans_and_question_bank(): void
    {
        $year = AcademicYear::create(['siap_id' => 94, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 905, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $class1 = Classroom::create(['siap_id' => 915, 'academic_year_id' => $year->id, 'name' => 'X TKR 1', 'grade_level' => 10]);

        $owner = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $peer = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class1->id, 'subject_id' => $subject->id, 'title' => 'BIN X TKR 1', 'status' => 'active']);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $peer->id, 'is_primary' => true]);

        $ownerPlan = TeachingPlan::create([
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'grade_level' => 10,
            'teacher_id' => $owner->id,
            'title' => 'Perangkat Privat Pemilik',
            'capaian_pembelajaran' => 'CP Privat',
            'is_shared' => false,
        ]);
        LearningObjective::create([
            'teaching_plan_id' => $ownerPlan->id,
            'code' => 'TP-1',
            'title' => 'TP dari Pemilik',
            'jp_allocation' => 4,
        ]);

        $peerPlan = TeachingPlan::create([
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'grade_level' => 10,
            'teacher_id' => $peer->id,
            'title' => 'Perangkat Rekan',
            'is_shared' => false,
        ]);

        // Peer cannot view or copy private plan, and cannot toggle owner's share flag
        $this->actingAs($peer)->get(route('teaching-plans.show', $ownerPlan))->assertForbidden();
        $this->actingAs($peer)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.toggle-share', $ownerPlan), ['_token' => 'test-token'])
            ->assertForbidden();
        $this->actingAs($peer)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.copy-from', $peerPlan), ['_token' => 'test-token', 'source_plan_id' => $ownerPlan->id])
            ->assertForbidden();

        // Owner toggles share ON
        $this->actingAs($owner)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.toggle-share', $ownerPlan), ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertTrue($ownerPlan->fresh()->is_shared);

        // Now peer can view (read-only) and copy into their own private plan
        $this->actingAs($peer)->get(route('teaching-plans.show', $ownerPlan))->assertOk();
        $this->actingAs($peer)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.copy-from', $peerPlan), ['_token' => 'test-token', 'source_plan_id' => $ownerPlan->id])
            ->assertRedirect();

        $this->assertSame('CP Privat', $peerPlan->fresh()->capaian_pembelajaran);
        $this->assertFalse($peerPlan->fresh()->is_shared);
        $this->assertSame(1, $peerPlan->learningObjectives()->count());

        // Question bank owner authority
        $privateQuestion = QuestionBank::create([
            'teacher_id' => $owner->id,
            'subject_id' => $subject->id,
            'difficulty' => 'sedang',
            'type' => 'multiple_choice',
            'question_text' => 'Soal Privat Pemilik',
            'points' => 10,
            'is_shared' => false,
        ]);

        $this->actingAs($peer)->get(route('question-bank.index'))
            ->assertOk()
            ->assertDontSee('Soal Privat Pemilik');

        $this->actingAs($owner)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.toggle-share', $privateQuestion), ['_token' => 'test-token'])
            ->assertRedirect();

        $this->actingAs($peer)->get(route('question-bank.index'))
            ->assertOk()
            ->assertSee('Soal Privat Pemilik');

        // Peer still cannot delete owner's shared question
        $this->actingAs($peer)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('question-bank.destroy', $privateQuestion), ['_token' => 'test-token'])
            ->assertForbidden();
    }

    public function test_teacher_can_download_excel_template_preview_and_import_teaching_plan(): void
    {
        $year = AcademicYear::create(['siap_id' => 95, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 906, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $plan = TeachingPlan::create([
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'grade_level' => 10,
            'teacher_id' => $teacher->id,
            'title' => 'Perangkat Awal',
        ]);

        $this->actingAs($teacher)
            ->get(route('teaching-plans.template.download', $plan))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $spreadsheet = new Spreadsheet;
        $s1 = $spreadsheet->getActiveSheet();
        $s1->setTitle('CP_Prota_KKTP');
        $s1->fromArray([
            ['Kolom', 'Isi Nilai'],
            ['Judul_Perangkat', 'Perangkat Impor Excel'],
            ['Fase', 'Fase E'],
            ['Capaian_Pembelajaran', 'CP dari Excel Offline'],
            ['Minggu_Efektif', 36],
            ['Target_Total_JP', 108],
            ['Pendekatan_KKTP', 'interval'],
            ['Skor_Minimal_KKTP', 78],
            ['Pedoman_KKTP', 'Minimal 78'],
        ]);

        $s2 = $spreadsheet->createSheet();
        $s2->setTitle('ATP');
        $s2->fromArray([
            ['Kode_TP', 'Tujuan_Pembelajaran', 'Materi_Pokok_Deskripsi', 'Alokasi_JP', 'Periode_Bulan'],
            ['TP-X1', 'Menganalisis Teks LHO', 'Struktur LHO', 12, 'Juli'],
        ]);

        $s3 = $spreadsheet->createSheet();
        $s3->setTitle('Modul_Ajar');
        $s3->fromArray([
            ['Judul_Modul_Ajar', 'Kode_TP_Terkait', 'Tujuan_Pembelajaran', 'Langkah_Kegiatan', 'Rencana_Asesmen', 'Tautan_Video_atau_Drive'],
            ['Modul LHO Offline', 'TP-X1', 'Memahami LHO', 'Observasi bengkel', 'LKPD', 'https://drive.google.com/file/d/abc123'],
        ]);

        $tmpPath = sys_get_temp_dir().'/test_teaching_plan_'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        $uploadedFile = new UploadedFile($tmpPath, 'perangkat.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('teaching-plans.import.preview', $plan), [
                '_token' => 'test-token',
                'excel_file' => $uploadedFile,
            ])
            ->assertOk()
            ->assertSee('Perangkat Impor Excel')
            ->assertSee('TP-X1')
            ->assertSee('Modul LHO Offline');

        $this->actingAs($teacher)
            ->post(route('teaching-plans.import.confirm', $plan), [
                '_token' => 'test-token',
                'import_mode' => 'replace',
            ])
            ->assertRedirect(route('teaching-plans.show', $plan));

        @unlink($tmpPath);

        $freshPlan = $plan->fresh();
        $this->assertSame('Perangkat Impor Excel', $freshPlan->title);
        $this->assertSame('CP dari Excel Offline', $freshPlan->capaian_pembelajaran);
        $this->assertSame(78, $freshPlan->kktp_min_score);
        $this->assertSame(1, $freshPlan->learningObjectives()->count());
        $this->assertSame('https://drive.google.com/file/d/abc123', $freshPlan->lessonPlans()->firstOrFail()->external_url);
    }

    public function test_teacher_can_bulk_import_all_five_cbtpgri_question_types_with_duplicate_detection(): void
    {
        $subject = Subject::create(['siap_id' => 907, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        QuestionBank::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'difficulty' => 'mudah',
            'type' => 'multiple_choice',
            'question_text' => 'Soal Duplikat Sudah Ada',
            'options_json' => ['A' => 'Satu', 'B' => 'Dua'],
            'correct_answer' => 'A',
            'points' => 10,
        ]);

        $this->actingAs($teacher)
            ->get(route('question-bank.template.download'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank_Soal');
        $sheet->fromArray([
            ['Mata_Pelajaran', 'Tingkat_Kelas', 'Bab_Kategori', 'Kisi_Kisi', 'Tipe', 'Kesulitan', 'Poin', 'Pertanyaan', 'Opsi_A', 'Opsi_B', 'Opsi_C', 'Opsi_D', 'Opsi_E', 'Pasangan_Menjodohkan', 'Kunci_Jawaban', 'Bagikan'],
            ['Bahasa Indonesia', 10, 'Bab 1', 'Kisi 1', 'PG', 'mudah', 10, 'Soal Duplikat Sudah Ada', 'A1', 'B1', '', '', '', '', 'A', 'TIDAK'],
            ['Bahasa Indonesia', 10, 'Bab 1', 'Kisi 1', 'PG', 'mudah', 10, 'Soal PG 5 Opsi Baru', 'A1', 'B1', 'C1', 'D1', 'E1', '', 'E', 'TIDAK'],
            ['Bahasa Indonesia', 10, 'Bab 1', 'Kisi 2', 'PG Kompleks', 'sedang', 15, 'Soal Kompleks Baru', 'A1', 'B1', 'C1', 'D1', 'E1', '', 'A,E', 'YA'],
            ['Bahasa Indonesia', 10, 'Bab 2', 'Kisi 3', 'Menjodohkan', 'sedang', 15, 'Soal Jodohkan Baru', '', '', '', '', '', 'Kiri1=Kanan1|Kiri2=Kanan2', '', 'TIDAK'],
            ['Bahasa Indonesia', 10, 'Bab 1', 'Kisi 4', 'Isian Singkat', 'mudah', 10, 'Soal Isian Baru', '', '', '', '', '', '', 'adalah', 'TIDAK'],
            ['Bahasa Indonesia', 10, 'Bab 1', 'Kisi 5', 'Esai', 'sulit', 20, 'Soal Esai Baru', '', '', '', '', '', '', '', 'TIDAK'],
        ]);

        $tmpPath = sys_get_temp_dir().'/test_qb_'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);
        $uploadedFile = new UploadedFile($tmpPath, 'bank_soal.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.import.preview'), [
                '_token' => 'test-token',
                'excel_file' => $uploadedFile,
            ])
            ->assertOk()
            ->assertSee('Soal PG 5 Opsi Baru')
            ->assertSee('soal duplikat terdeteksi');

        $this->actingAs($teacher)
            ->post(route('question-bank.import.confirm'), [
                '_token' => 'test-token',
            ])
            ->assertRedirect(route('question-bank.index'));

        @unlink($tmpPath);

        $this->assertSame(6, QuestionBank::where('teacher_id', $teacher->id)->count());
        $this->assertTrue(QuestionBank::where('question_text', 'Soal Kompleks Baru')->firstOrFail()->is_shared);
        $this->assertSame('multiple_choice_complex', QuestionBank::where('question_text', 'Soal Kompleks Baru')->firstOrFail()->type);
        $this->assertSame('matching', QuestionBank::where('question_text', 'Soal Jodohkan Baru')->firstOrFail()->type);
        $this->assertSame('short_answer', QuestionBank::where('question_text', 'Soal Isian Baru')->firstOrFail()->type);
    }

    public function test_teacher_can_package_questions_and_sync_cbt_exam_with_cbtpgri(): void
    {
        $year = AcademicYear::create(['siap_id' => 96, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subject = Subject::create(['siap_id' => 908, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $class = Classroom::create(['siap_id' => 916, 'academic_year_id' => $year->id, 'name' => 'X TKR 1', 'grade_level' => 'Tingkat X (Sepuluh)']);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $student = User::factory()->create(['role' => 'siswa', 'username' => '008999111', 'must_change_password' => false]);

        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $class->id, 'subject_id' => $subject->id, 'title' => 'BIN X TKR 1', 'status' => 'active']);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);

        $q1 = QuestionBank::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'difficulty' => 'mudah',
            'type' => 'multiple_choice',
            'question_text' => 'Soal PTS 1',
            'options_json' => ['A' => 'Satu', 'B' => 'Dua', 'E' => 'Lima'],
            'correct_answer' => 'E',
            'points' => 10,
        ]);

        // Full-page 1-step Buat Ujian Sekolah (CBT) & reject UH
        $this->actingAs($teacher)
            ->get(route('exams.create'))
            ->assertOk()
            ->assertSee('Buat Ujian Sekolah (CBT)');

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.store'), [
                '_token' => 'test-token',
                'course_ids' => [$course->id],
                'title' => 'UH Ditolak di CBT',
                'exam_type' => 'UH',
                'duration_minutes' => 60,
            ])
            ->assertSessionHasErrors('exam_type');

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.store'), [
                '_token' => 'test-token',
                'course_ids' => [$course->id],
                'title' => 'Simulasi ANBK 1 Langkah',
                'exam_type' => 'Simulasi',
                'duration_minutes' => 90,
                'display_question_count' => 1,
                'question_bank_ids' => [$q1->id],
            ])
            ->assertRedirect();

        $oneStepExam = CbtExam::where('title', 'Simulasi ANBK 1 Langkah')->firstOrFail();
        $this->assertSame(1, $oneStepExam->questions()->count());
        $this->assertSame(1, (int) $oneStepExam->display_question_count);

        $exam = CbtExam::create([
            'course_id' => $course->id,
            'title' => 'PTS Bahasa Indonesia X',
            'exam_type' => 'PTS',
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        // Full-page Question Bank Picker for CbtExam
        $this->actingAs($teacher)
            ->get(route('exams.questions.pick', $exam))
            ->assertOk()
            ->assertSee('Pilih Soal dari Bank Soal')
            ->assertSee($q1->question_text);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('exams.questions.attach', $exam), [
                '_token' => 'test-token',
                'question_bank_ids' => [$q1->id],
            ])
            ->assertRedirect(route('exams.show', $exam));

        $this->assertSame(1, $exam->questions()->count());

        Http::fake([
            '*/api/v1/lms/sync-paket-soal' => Http::response([
                'success' => true,
                'id_bank' => 555,
                'bank_kode' => 'LMS-1-PTS',
                'questions_count' => 1,
            ], 200),
            '*/api/v1/lms/paket-soal/LMS-1-PTS/results' => Http::response([
                'success' => true,
                'bank_kode' => 'LMS-1-PTS',
                'results' => [
                    ['username' => '008999111', 'nisn' => '008999111', 'score' => 92.5],
                ],
            ], 200),
        ]);

        $this->actingAs($teacher)
            ->post(route('exams.push-cbt', $exam), ['_token' => 'test-token'])
            ->assertRedirect();

        $exam->refresh();
        $this->assertSame('LMS-1-PTS', $exam->cbt_exam_code);
        $this->assertSame(555, (int) $exam->cbt_package_id);
        $this->assertNotNull($exam->last_synced_at);

        $this->actingAs($teacher)
            ->post(route('exams.pull-cbt', $exam), ['_token' => 'test-token'])
            ->assertRedirect();

        $result = CbtExamResult::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($result);
        $this->assertSame(92.5, (float) $result->score);
    }

    public function test_teacher_can_edit_question_upload_media_and_scope_by_role(): void
    {
        Storage::fake('local');

        $year = AcademicYear::create(['siap_id' => 98, 'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $subjectBin = Subject::create(['siap_id' => 911, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $subjectMat = Subject::create(['siap_id' => 912, 'code' => 'MAT', 'name' => 'Matematika']);
        $classX = Classroom::create(['siap_id' => 918, 'academic_year_id' => $year->id, 'name' => 'X TKJ 1', 'grade_level' => 'Tingkat X (Sepuluh)', 'department' => 'TKJ']);

        $teacherBinX = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $otherTeacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $course = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $classX->id, 'subject_id' => $subjectBin->id, 'title' => 'BIN X TKJ 1', 'status' => 'active']);
        CourseTeacher::create(['course_id' => $course->id, 'teacher_id' => $teacherBinX->id, 'is_primary' => true]);

        // Shared BIN X (should be visible), Shared BIN XI (should NOT be visible), Shared MAT X (should NOT be visible)
        QuestionBank::create([
            'teacher_id' => $otherTeacher->id,
            'subject_id' => $subjectBin->id,
            'grade_level' => 10,
            'difficulty' => 'sedang',
            'type' => 'multiple_choice',
            'question_text' => 'Soal Dibagikan BIN Kelas 10',
            'points' => 10,
            'is_shared' => true,
        ]);
        QuestionBank::create([
            'teacher_id' => $otherTeacher->id,
            'subject_id' => $subjectBin->id,
            'grade_level' => 11,
            'difficulty' => 'sedang',
            'type' => 'multiple_choice',
            'question_text' => 'Soal Dibagikan BIN Kelas 11',
            'points' => 10,
            'is_shared' => true,
        ]);
        QuestionBank::create([
            'teacher_id' => $otherTeacher->id,
            'subject_id' => $subjectMat->id,
            'grade_level' => 10,
            'difficulty' => 'sedang',
            'type' => 'multiple_choice',
            'question_text' => 'Soal Dibagikan MAT Kelas 10',
            'points' => 10,
            'is_shared' => true,
        ]);

        $this->actingAs($teacherBinX)
            ->get(route('question-bank.index'))
            ->assertOk()
            ->assertSee('Soal Dibagikan BIN Kelas 10')
            ->assertDontSee('Soal Dibagikan BIN Kelas 11')
            ->assertDontSee('Soal Dibagikan MAT Kelas 10');

        // Cannot create question for un-taught subject
        $this->actingAs($teacherBinX)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subjectMat->id,
                'grade_level' => 10,
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Harusnya Ditolak',
                'points' => 10,
            ])
            ->assertForbidden();

        // Create Question with image & option image for taught subject/grade
        $qImg = UploadedFile::fake()->image('soal.png', 120, 80);
        $optAImg = UploadedFile::fake()->image('opsi_a.png', 80, 80);

        $this->actingAs($teacherBinX)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.store'), [
                '_token' => 'test-token',
                'subject_id' => $subjectBin->id,
                'grade_level' => 10,
                'department' => 'TKJ',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => '<p>Bacalah paragraf berikut.</p>',
                'question_image' => $qImg,
                'option_a' => '1',
                'option_b' => '2',
                'option_image_a' => $optAImg,
                'correct_answer' => 'A',
                'points' => 10,
            ])
            ->assertRedirect();

        $qb = QuestionBank::where('teacher_id', $teacherBinX->id)->firstOrFail();
        $this->assertSame('TKJ', $qb->department);
        $this->assertNotNull($qb->question_image);
        $this->assertNotEmpty($qb->option_images_json['A'] ?? null);

        // Stream media endpoint
        $this->actingAs($teacherBinX)
            ->get(route('question-bank.media', ['path' => $qb->question_image]))
            ->assertOk();

        // Edit question
        $this->actingAs($teacherBinX)
            ->withSession(['_token' => 'test-token'])
            ->put(route('question-bank.update', $qb), [
                '_token' => 'test-token',
                'subject_id' => $subjectBin->id,
                'grade_level' => 10,
                'department' => 'TKJ',
                'difficulty' => 'sulit',
                'type' => 'multiple_choice',
                'question_text' => '<p>Pertanyaan diperbarui.</p>',
                'option_a' => '1',
                'option_b' => '3',
                'correct_answer' => 'B',
                'points' => 15,
            ])
            ->assertRedirect();

        $qb->refresh();
        $this->assertSame('sulit', $qb->difficulty);
        $this->assertSame('B', $qb->correct_answer);
        $this->assertSame(15.0, (float) $qb->points);
    }

    public function test_teacher_can_download_word_template_and_import_docx_with_context_and_images(): void
    {
        Storage::fake('local');

        $subject = Subject::create(['siap_id' => 912, 'code' => 'BIN', 'name' => 'Bahasa Indonesia']);
        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);

        $download = $this->actingAs($teacher)
            ->get(route('question-bank.template-word.download'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $docxBytes = $download->streamedContent();
        $this->assertStringStartsWith('PK', $docxBytes);

        $checkTmp = tempnam(sys_get_temp_dir(), 'check_docx_').'.docx';
        file_put_contents($checkTmp, $docxBytes);
        $checkZip = new ZipArchive;
        $this->assertTrue($checkZip->open($checkTmp) === true);
        $this->assertNotFalse($checkZip->getFromName('word/document.xml'));
        $this->assertNotFalse($checkZip->getFromName('word/styles.xml'));
        $checkZip->close();
        @unlink($checkTmp);

        // Build minimal .docx with 1 table row + 1 embedded image
        $tmpDocx = tempnam(sys_get_temp_dir(), 'qb_word_').'.docx';
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmpDocx, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdImg1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/soal1.png"/></Relationships>');
        $zip->addFromString('word/media/soal1.png', $pngBytes);

        $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
  <w:body>
    <w:tbl>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Pertanyaan</w:t></w:r></w:p></w:tc>
        <w:tc>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Paragraf Pertama</w:t></w:r></w:p>
          <w:p><w:r><w:t>Paragraf Kedua</w:t></w:r><w:r><w:drawing><a:blip r:embed="rIdImg1"/></w:drawing></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Opsi A</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Jawaban A</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Opsi B</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Jawaban B</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Opsi C</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Jawaban C</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Opsi D</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Jawaban D</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Opsi E</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Jawaban E</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Kunci Jawaban</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>A</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Kesulitan</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>sedang</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Kisi-Kisi</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Memahami teks bacaan</w:t></w:r></w:p></w:tc></w:tr>
      <w:tr><w:tc><w:p><w:r><w:t>Poin</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>20</w:t></w:r></w:p></w:tc></w:tr>
    </w:tbl>
  </w:body>
</w:document>
XML;
        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        $wordUpload = new UploadedFile($tmpDocx, 'soal.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.import.preview'), [
                '_token' => 'test-token',
                'file_format' => 'word',
                'subject_id' => $subject->id,
                'grade_level' => 10,
                'department' => 'TKJ',
                'type' => 'multiple_choice',
                'is_shared' => 1,
                'import_file' => $wordUpload,
            ])
            ->assertOk()
            ->assertSee('Paragraf Pertama')
            ->assertSee('TKJ');

        $this->actingAs($teacher)
            ->withSession(['_token' => 'test-token'])
            ->post(route('question-bank.import.confirm'), ['_token' => 'test-token'])
            ->assertRedirect(route('question-bank.index'));

        $imported = QuestionBank::first();
        $this->assertNotNull($imported);
        $this->assertSame($subject->id, $imported->subject_id);
        $this->assertSame(10, $imported->grade_level);
        $this->assertSame('TKJ', $imported->department);
        $this->assertTrue($imported->is_shared);
        $this->assertStringContainsString('<strong>Paragraf Pertama</strong>', $imported->question_text);
        $this->assertNotNull($imported->question_image);

        @unlink($tmpDocx);
    }
}
