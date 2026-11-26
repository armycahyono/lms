<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Discussion;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2LearningTest extends TestCase
{
    use RefreshDatabase;

    private function seedCourseContext(): array
    {
        $year = AcademicYear::create(['siap_id' => 1, 'name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        $classroom = Classroom::create(['siap_id' => 10, 'academic_year_id' => $year->id, 'name' => 'X RPL 1']);
        $subject = Subject::create(['siap_id' => 20, 'code' => 'PBO', 'name' => 'PBO']);

        $guru = User::factory()->guru()->create(['username' => 'guru1']);
        $siswa = User::factory()->siswa()->create(['username' => '0081234567']);
        $outsider = User::factory()->siswa()->create(['username' => '0089999999']);

        ClassroomStudent::create([
            'classroom_id' => $classroom->id,
            'student_user_id' => $siswa->id,
            'status' => 'active',
        ]);

        $course = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'PBO - X RPL 1',
            'status' => 'draft',
        ]);
        $course->teachers()->attach($guru->id, ['is_primary' => true]);

        return compact('year', 'classroom', 'subject', 'guru', 'siswa', 'outsider', 'course');
    }

    public function test_admin_can_reset_student_password_to_nisn(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'admin1']);
        $siswa = User::factory()->siswa()->create([
            'username' => '0081234567',
            'password' => Hash::make('CustomPass99'),
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.students.reset-password', $siswa))
            ->assertRedirect();

        $siswa->refresh();
        $this->assertTrue($siswa->must_change_password);
        $this->assertTrue(Hash::check('0081234567', $siswa->password));
    }

    public function test_teacher_can_open_course_and_auto_enroll_students(): void
    {
        ['guru' => $guru, 'siswa' => $siswa, 'course' => $course] = $this->seedCourseContext();

        $this->actingAs($guru)
            ->post(route('courses.open', $course))
            ->assertRedirect();

        $course->refresh();
        $this->assertSame('active', $course->status);
        $this->assertNotNull($course->opened_at);
        $this->assertTrue($course->students()->where('users.id', $siswa->id)->exists());
    }

    public function test_teacher_can_duplicate_modules_and_materials_from_another_course(): void
    {
        ['year' => $year, 'classroom' => $classroom, 'guru' => $guru, 'course' => $targetCourse] = $this->seedCourseContext();

        $otherSubject = Subject::create(['siap_id' => 21, 'code' => 'WEB', 'name' => 'Pemrograman Web']);
        $sourceCourse = Course::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $otherSubject->id,
            'title' => 'Web - X RPL 1',
            'status' => 'active',
        ]);
        $module = CourseModule::create(['course_id' => $sourceCourse->id, 'title' => 'Bab 1', 'sort_order' => 1, 'is_published' => true]);
        Material::create(['course_id' => $sourceCourse->id, 'module_id' => $module->id, 'title' => 'HTML Dasar', 'type' => 'text', 'content' => 'Halo', 'is_published' => true]);

        $this->actingAs($guru)
            ->post(route('courses.duplicate', $targetCourse), ['source_course_id' => $sourceCourse->id])
            ->assertRedirect();

        $this->assertSame(1, $targetCourse->modules()->count());
        $this->assertSame(1, $targetCourse->materials()->count());
        $this->assertFalse($targetCourse->modules()->first()->is_published);
    }

    public function test_private_material_download_progress_and_discussions(): void
    {
        Storage::fake('local');
        ['guru' => $guru, 'siswa' => $siswa, 'outsider' => $outsider, 'course' => $course] = $this->seedCourseContext();

        $this->actingAs($guru)->post(route('courses.open', $course));
        $this->actingAs($guru)->post(route('courses.modules.store', $course), ['title' => 'Bab 1', 'is_published' => true]);
        $module = $course->modules()->firstOrFail();

        $this->actingAs($guru)->post(route('courses.materials.store', $course), [
            'module_id' => $module->id,
            'title' => 'Slide Bab 1',
            'type' => 'file',
            'file' => UploadedFile::fake()->create('slide.pdf', 100, 'application/pdf'),
            'is_published' => true,
        ]);

        $material = $course->materials()->firstOrFail();
        Storage::disk('local')->assertExists($material->file_path);

        $this->actingAs($outsider)
            ->get(route('courses.materials.download', [$course, $material]))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('courses.materials.download', [$course, $material]))
            ->assertOk();

        $this->actingAs($siswa)
            ->post(route('courses.materials.complete', [$course, $material]))
            ->assertRedirect();

        $this->assertTrue(MaterialProgress::where('material_id', $material->id)->where('student_id', $siswa->id)->exists());

        $this->actingAs($siswa)
            ->post(route('courses.discussions.store', $course), [
                'material_id' => $material->id,
                'body' => 'Izin bertanya halaman 2.',
            ])
            ->assertRedirect();

        $this->assertSame(1, Discussion::where('discussable_id', $material->id)->count());
    }

    public function test_assignment_submission_and_teacher_grading_validation(): void
    {
        Storage::fake('local');
        ['guru' => $guru, 'siswa' => $siswa, 'course' => $course] = $this->seedCourseContext();

        $this->actingAs($guru)->post(route('courses.open', $course));
        $this->actingAs($guru)->post(route('courses.assignments.store', $course), [
            'title' => 'Tugas 1',
            'instructions' => 'Kerjakan dengan baik.',
            'max_score' => 100,
            'is_published' => true,
        ]);

        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();

        $this->actingAs($siswa)->post(route('courses.assignments.submissions.store', [$course, $assignment]), [
            'content' => 'Jawaban saya',
            'file' => UploadedFile::fake()->create('jawaban.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $siswa->id)
            ->firstOrFail();
        Storage::disk('local')->assertExists($submission->file_path);

        $this->actingAs($guru)->post(route('courses.assignments.submissions.grade', [$course, $assignment, $submission]), [
            'score' => 92,
            'feedback' => 'Bagus sekali!',
            'is_validated_for_siap' => 1,
        ])->assertRedirect();

        $submission->refresh();
        $this->assertSame('92.00', $submission->score);
        $this->assertTrue($submission->is_validated_for_siap);
    }
}
