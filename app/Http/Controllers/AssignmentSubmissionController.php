<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentSubmissionController extends Controller
{
    public function store(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($assignment->course_id === $course->id && $assignment->is_published, 404);
        abort_unless($request->user()->isSiswa(), 403);

        $isLate = $assignment->due_at !== null && now()->greaterThan($assignment->due_at);
        if ($isLate && ! $assignment->allow_late) {
            return back()->withErrors([
                'content' => 'Batas waktu pengumpulan tugas telah berakhir.',
            ]);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'required_without:file'],
            'file' => ['nullable', 'file', 'max:20480', 'required_without:content'],
        ]);

        $existing = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        $filePath = $existing?->file_path;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store("submissions/{$assignment->id}", 'local');
        }

        AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $request->user()->id,
            ],
            [
                'content' => $validated['content'] ?? $existing?->content,
                'file_path' => $filePath,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ]
        );

        return back()->with('status', 'Jawaban tugas berhasil dikirim.');
    }

    public function download(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission): StreamedResponse
    {
        Gate::authorize('view', $course);
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);

        $canDownload = Gate::allows('manage', $course) || $submission->student_id === $request->user()->id;
        abort_unless($canDownload, 403);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path);
    }

    public function grade(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => ['nullable', 'string'],
            'is_validated_for_siap' => ['boolean'],
        ]);

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
            'is_validated_for_siap' => $request->boolean('is_validated_for_siap'),
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'assignment.graded',
            'subject_type' => AssignmentSubmission::class,
            'subject_id' => $submission->id,
            'properties_json' => [
                'score' => $validated['score'],
                'validated' => $request->boolean('is_validated_for_siap'),
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Nilai dan umpan balik siswa berhasil disimpan.');
    }
}
