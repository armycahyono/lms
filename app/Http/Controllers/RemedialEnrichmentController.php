<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\RemedialEnrichment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RemedialEnrichmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'learning_objective_id' => ['nullable', 'integer', 'exists:learning_objectives,id'],
            'program_type' => ['required', 'in:remedial,pengayaan'],
            'initial_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'activity_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:planned,completed'],
        ]);

        RemedialEnrichment::create([
            'course_id' => $course->id,
            'student_id' => $validated['student_id'],
            'learning_objective_id' => $validated['learning_objective_id'] ?? null,
            'program_type' => $validated['program_type'],
            'initial_score' => $validated['initial_score'] ?? null,
            'final_score' => $validated['final_score'] ?? null,
            'activity_notes' => $validated['activity_notes'] ?? null,
            'status' => $validated['status'] ?? ($validated['final_score'] !== null ? 'completed' : 'planned'),
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Catatan program remedial/pengayaan berhasil disimpan.');
    }

    public function destroy(Course $course, RemedialEnrichment $remedialEnrichment): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($remedialEnrichment->course_id === $course->id, 404);

        $remedialEnrichment->delete();

        return back()->with('status', 'Catatan remedial/pengayaan dihapus.');
    }
}
