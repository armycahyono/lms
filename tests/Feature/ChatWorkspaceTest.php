<?php

namespace Tests\Feature;

use App\Livewire\ChatWorkspace;
use App\Models\AcademicYear;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseTeacher;
use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ChatWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_students_can_use_course_direct_topic_chat_and_material_reference(): void
    {
        $year = AcademicYear::create([
            'siap_id' => 30,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $classA = Classroom::create(['siap_id' => 301, 'academic_year_id' => $year->id, 'name' => 'X TKJ 1', 'grade_level' => 10]);
        $classB = Classroom::create(['siap_id' => 302, 'academic_year_id' => $year->id, 'name' => 'XI TKJ 1', 'grade_level' => 11]);
        $subject = Subject::create(['siap_id' => 3001, 'code' => 'INF', 'name' => 'Informatika']);

        $courseA = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $classA->id, 'subject_id' => $subject->id, 'title' => 'Informatika X TKJ 1', 'status' => 'active']);
        $courseB = Course::create(['academic_year_id' => $year->id, 'classroom_id' => $classB->id, 'subject_id' => $subject->id, 'title' => 'Informatika XI TKJ 1', 'status' => 'active']);

        $teacher = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $studentA1 = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);
        $studentA2 = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);
        $outsider = User::factory()->create(['role' => 'siswa', 'must_change_password' => false]);

        CourseTeacher::create(['course_id' => $courseA->id, 'teacher_id' => $teacher->id, 'is_primary' => true]);
        CourseEnrollment::create(['course_id' => $courseA->id, 'student_id' => $studentA1->id, 'enrolled_at' => now(), 'status' => 'active']);
        CourseEnrollment::create(['course_id' => $courseA->id, 'student_id' => $studentA2->id, 'enrolled_at' => now(), 'status' => 'active']);
        CourseEnrollment::create(['course_id' => $courseB->id, 'student_id' => $outsider->id, 'enrolled_at' => now(), 'status' => 'active']);

        $material = Material::create([
            'course_id' => $courseA->id,
            'title' => 'Bab 1: Berpikir Komputasional',
            'type' => 'article',
            'content' => 'Konten materi',
            'is_published' => true,
        ]);

        // Page loads and shows "Tanyakan di Chat" on material
        $this->actingAs($studentA1)->get(route('courses.materials.show', [$courseA, $material]))
            ->assertOk()
            ->assertSee('Tanyakan di Chat');

        $this->actingAs($studentA1)->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Ruang Chat');

        // Student asks about material in course chat room
        Livewire::actingAs($studentA1)
            ->test(ChatWorkspace::class, [
                'initialCourseId' => $courseA->id,
                'initialRefType' => 'material',
                'initialRefId' => $material->id,
            ])
            ->set('messageBody', 'Bu, mohon penjelasan bagian abstraksi.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $courseRoom = ChatRoom::where('type', 'course')->where('course_id', $courseA->id)->first();
        $this->assertNotNull($courseRoom);
        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $courseRoom->id,
            'user_id' => $studentA1->id,
            'reference_type' => Material::class,
            'reference_id' => $material->id,
            'body' => 'Bu, mohon penjelasan bagian abstraksi.',
        ]);

        // Student can DM classmate, but cannot DM outsider from another class
        Livewire::actingAs($studentA1)
            ->test(ChatWorkspace::class)
            ->set('showNewDirectModal', true)
            ->call('startDirectChat', $outsider->id)
            ->assertHasErrors('contactSearch')
            ->call('startDirectChat', $studentA2->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'direct',
            'direct_key' => ChatRoom::directKeyFor($studentA1->id, $studentA2->id),
        ]);

        // Teacher creates topic room, locks it, and student cannot send while locked
        $lwTeacher = Livewire::actingAs($teacher)
            ->test(ChatWorkspace::class)
            ->set('topicTitle', 'Persiapan PAS Informatika')
            ->set('topicCourseId', $courseA->id)
            ->call('createTopicRoom')
            ->assertHasNoErrors();

        $topicRoom = ChatRoom::where('type', 'topic')->where('title', 'Persiapan PAS Informatika')->firstOrFail();
        $lwTeacher->call('toggleLock');
        $this->assertTrue($topicRoom->fresh()->is_locked);

        Livewire::actingAs($studentA1)
            ->test(ChatWorkspace::class)
            ->call('selectRoom', $topicRoom->id)
            ->set('messageBody', 'Coba kirim saat terkunci')
            ->call('sendMessage')
            ->assertHasErrors('messageBody');

        // Attachment download is protected by room membership
        Storage::fake('local');
        Storage::disk('local')->put("chat/{$courseRoom->id}/soal.pdf", 'pdf-content');
        $attMsg = ChatMessage::create([
            'chat_room_id' => $courseRoom->id,
            'user_id' => $teacher->id,
            'body' => 'File latihan',
            'attachment_path' => "chat/{$courseRoom->id}/soal.pdf",
            'attachment_name' => 'soal.pdf',
        ]);

        $this->actingAs($outsider)
            ->get(route('chat.attachments.download', $attMsg))
            ->assertForbidden();

        $this->actingAs($studentA1)
            ->get(route('chat.attachments.download', $attMsg))
            ->assertOk();

        // When room has >120 messages, ChatWorkspace shows the latest 120 (not the oldest 120)
        for ($i = 1; $i <= 125; $i++) {
            ChatMessage::create([
                'chat_room_id' => $courseRoom->id,
                'user_id' => $teacher->id,
                'body' => "Pesan urutan ke-{$i}",
            ]);
        }

        Livewire::actingAs($studentA1)
            ->test(ChatWorkspace::class, ['initialCourseId' => $courseA->id])
            ->assertSee('Pesan urutan ke-125')
            ->assertDontSee('Pesan urutan ke-1"');
    }
}
