<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Course;
use App\Services\LmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnnouncementController extends Controller
{
    public function storeSchool(Request $request, LmsNotificationService $notifications): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_pinned' => ['boolean'],
        ]);

        $announcement = Announcement::create([
            'course_id' => null,
            'academic_year_id' => AcademicYear::active()?->id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $request->boolean('is_pinned', true),
            'published_at' => now(),
        ]);

        $notifications->notifyAllActiveUsers(
            'announcement',
            'Pengumuman Sekolah: '.$announcement->title,
            $announcement->body,
            ['announcement_id' => $announcement->id],
            $request->user()->id
        );

        return back()->with('status', 'Pengumuman sekolah berhasil diterbitkan.');
    }

    public function storeCourse(Request $request, Course $course, LmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_pinned' => ['boolean'],
        ]);

        $announcement = Announcement::create([
            'course_id' => $course->id,
            'academic_year_id' => $course->academic_year_id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $request->boolean('is_pinned', false),
            'published_at' => now(),
        ]);

        $notifications->notifyCourseStudents(
            $course,
            'announcement',
            'Pengumuman '.$course->title.': '.$announcement->title,
            $announcement->body,
            ['course_id' => $course->id, 'announcement_id' => $announcement->id]
        );

        return back()->with('status', 'Pengumuman kelas berhasil diterbitkan.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        $canDelete = $user->isAdmin()
            || $announcement->user_id === $user->id
            || ($announcement->course && Gate::allows('manage', $announcement->course));

        abort_unless($canDelete, 403);

        $announcement->delete();

        return back()->with('status', 'Pengumuman berhasil dihapus.');
    }
}
