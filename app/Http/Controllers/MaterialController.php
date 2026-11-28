<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Services\LmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function store(Request $request, Course $course, LmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,file,video_link'],
            'category' => ['nullable', 'in:materi,lkpd,buku,presentasi,video'],
            'content' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:20480'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'is_published' => ['boolean'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store("materials/{$course->id}", 'local');
        }

        $nextOrder = ((int) $course->materials()->max('sort_order')) + 1;

        $material = Material::create([
            'course_id' => $course->id,
            'module_id' => $validated['module_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? 'materi',
            'content' => $validated['content'] ?? null,
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'sort_order' => $nextOrder,
            'is_published' => $request->boolean('is_published', true),
        ]);

        if ($material->is_published) {
            $notifications->notifyCourseStudents(
                $course,
                'material',
                'Materi Baru: '.$material->title,
                "Guru menambahkan materi baru di kelas {$course->title}.",
                ['course_id' => $course->id, 'material_id' => $material->id]
            );
        }

        return back()->with('status', 'Materi berhasil ditambahkan.');
    }

    public function show(Request $request, Course $course, Material $material): View
    {
        Gate::authorize('view', $course);
        abort_unless($material->course_id === $course->id, 404);

        $canManage = Gate::allows('manage', $course);
        abort_unless($canManage || $material->is_published, 403);

        if ($request->user()->isSiswa()) {
            LearningAttendanceController::recordAutoDigital($course, $request->user(), $request->ip());
        }

        $material->load([
            'module',
            'discussions' => fn ($q) => $q->whereNull('parent_id')->with(['user', 'replies.user'])->latest('id'),
        ]);

        $isCompleted = $request->user()->isSiswa()
            ? MaterialProgress::where('material_id', $material->id)
                ->where('student_id', $request->user()->id)
                ->exists()
            : false;

        return view('materials.show', [
            'course' => $course,
            'material' => $material,
            'canManage' => $canManage,
            'isCompleted' => $isCompleted,
        ]);
    }

    public function download(Course $course, Material $material): StreamedResponse
    {
        Gate::authorize('view', $course);
        abort_unless($material->course_id === $course->id, 404);
        abort_unless(Gate::allows('manage', $course) || $material->is_published, 403);
        abort_unless($material->file_path && Storage::disk('local')->exists($material->file_path), 404);

        return Storage::disk('local')->download($material->file_path);
    }

    public function toggleComplete(Request $request, Course $course, Material $material): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($material->course_id === $course->id, 404);
        abort_unless($request->user()->isSiswa(), 403);

        $existing = MaterialProgress::where('material_id', $material->id)
            ->where('student_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Status selesai materi dibatalkan.';
        } else {
            MaterialProgress::create([
                'material_id' => $material->id,
                'student_id' => $request->user()->id,
                'completed_at' => now(),
            ]);
            $message = 'Materi ditandai selesai.';
        }

        return back()->with('status', $message);
    }

    public function togglePublish(Course $course, Material $material): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($material->course_id === $course->id, 404);

        $material->update(['is_published' => ! $material->is_published]);

        return back()->with('status', 'Status publikasi materi diperbarui.');
    }

    public function destroy(Course $course, Material $material): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($material->course_id === $course->id, 404);

        $material->delete();

        return back()->with('status', 'Materi dihapus.');
    }
}
