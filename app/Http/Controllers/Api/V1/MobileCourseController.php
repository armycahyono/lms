<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class MobileCourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->where('courses.status', 'active')
            ->with(['classroom:id,name', 'subject:id,code,name', 'teachers:id,name'])
            ->get();

        return response()->json([
            'data' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'classroom' => $course->classroom?->name,
                'subject' => $course->subject?->name,
                'teachers' => $course->teachers->pluck('name')->values(),
            ]),
        ]);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('view', $course);

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.materials' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'assignments' => fn ($q) => $q->where('is_published', true)->latest('id'),
            'quizzes' => fn ($q) => $q->where('is_published', true)->latest('id'),
        ]);

        return response()->json([
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'modules' => $course->modules->map(fn ($module) => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'materials' => $module->materials->map(fn (Material $material) => [
                        'id' => $material->id,
                        'title' => $material->title,
                        'type' => $material->type,
                        'content' => $material->content,
                        'external_url' => $material->external_url,
                        'download_url' => $material->file_path
                            ? URL::temporarySignedRoute('api.v1.signed-files.material', now()->addMinutes(30), ['material' => $material->id])
                            : null,
                    ]),
                ]),
                'assignments' => $course->assignments->map(fn (Assignment $assignment) => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'instructions' => $assignment->instructions,
                    'due_at' => $assignment->due_at?->toIso8601String(),
                    'max_score' => $assignment->max_score,
                    'attachment_url' => $assignment->attachment_path
                        ? URL::temporarySignedRoute('api.v1.signed-files.assignment', now()->addMinutes(30), ['assignment' => $assignment->id])
                        : null,
                ]),
                'quizzes' => $course->quizzes->map(fn ($quiz) => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'duration_minutes' => $quiz->duration_minutes,
                    'max_attempts' => $quiz->max_attempts,
                ]),
            ],
        ]);
    }

    public function completeMaterial(Request $request, Material $material): JsonResponse
    {
        Gate::authorize('view', $material->course);

        MaterialProgress::firstOrCreate(
            [
                'material_id' => $material->id,
                'student_id' => $request->user()->id,
            ],
            ['completed_at' => now()]
        );

        return response()->json(['status' => 'completed']);
    }

    public function submitAssignment(Request $request, Assignment $assignment): JsonResponse
    {
        Gate::authorize('view', $assignment->course);
        abort_unless($assignment->is_published && $request->user()->isSiswa(), 403);

        $isLate = $assignment->due_at !== null && now()->greaterThan($assignment->due_at);
        if ($isLate && ! $assignment->allow_late) {
            return response()->json(['message' => 'Batas waktu pengumpulan tugas telah berakhir.'], 422);
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

        $submission = AssignmentSubmission::updateOrCreate(
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

        return response()->json([
            'status' => 'submitted',
            'submission_id' => $submission->id,
            'is_late' => $submission->is_late,
        ]);
    }
}
