<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'allow_late' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store("assignments/{$course->id}", 'local');
        }

        Assignment::create([
            'course_id' => $course->id,
            'module_id' => $validated['module_id'] ?? null,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'attachment_path' => $attachmentPath,
            'due_at' => $validated['due_at'] ?? null,
            'max_score' => $validated['max_score'],
            'allow_late' => $request->boolean('allow_late', true),
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('status', 'Tugas berhasil dibuat.');
    }

    public function show(Request $request, Course $course, Assignment $assignment): View
    {
        Gate::authorize('view', $course);
        abort_unless($assignment->course_id === $course->id, 404);

        $canManage = Gate::allows('manage', $course);
        abort_unless($canManage || $assignment->is_published, 403);

        $submissions = $canManage
            ? $assignment->submissions()->with('student')->latest('submitted_at')->get()
            : collect();

        $mySubmission = $request->user()->isSiswa()
            ? $assignment->submissions()->where('student_id', $request->user()->id)->first()
            : null;

        return view('assignments.show', [
            'course' => $course,
            'assignment' => $assignment,
            'canManage' => $canManage,
            'submissions' => $submissions,
            'mySubmission' => $mySubmission,
        ]);
    }

    public function downloadAttachment(Course $course, Assignment $assignment): StreamedResponse
    {
        Gate::authorize('view', $course);
        abort_unless($assignment->course_id === $course->id, 404);
        abort_unless(Gate::allows('manage', $course) || $assignment->is_published, 403);
        abort_unless($assignment->attachment_path && Storage::disk('local')->exists($assignment->attachment_path), 404);

        return Storage::disk('local')->download($assignment->attachment_path);
    }

    public function togglePublish(Course $course, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($assignment->course_id === $course->id, 404);

        $assignment->update(['is_published' => ! $assignment->is_published]);

        return back()->with('status', 'Status publikasi tugas diperbarui.');
    }

    public function destroy(Course $course, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($assignment->course_id === $course->id, 404);

        $assignment->delete();

        return back()->with('status', 'Tugas dihapus.');
    }
}
