<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Discussion;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DiscussionController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('view', $course);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:discussions,id'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
        ]);

        $discussableType = null;
        $discussableId = null;

        if (! empty($validated['material_id'])) {
            $material = Material::where('course_id', $course->id)->findOrFail($validated['material_id']);
            $discussableType = Material::class;
            $discussableId = $material->id;
        }

        if (! empty($validated['parent_id'])) {
            $parent = Discussion::where('course_id', $course->id)->findOrFail($validated['parent_id']);
            $discussableType = $parent->discussable_type;
            $discussableId = $parent->discussable_id;
        }

        Discussion::create([
            'course_id' => $course->id,
            'discussable_type' => $discussableType,
            'discussable_id' => $discussableId,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Komentar diskusi berhasil dikirim.');
    }

    public function destroy(Request $request, Course $course, Discussion $discussion): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($discussion->course_id === $course->id, 404);
        abort_unless($discussion->user_id === $request->user()->id || Gate::allows('manage', $course), 403);

        $discussion->delete();

        return back()->with('status', 'Komentar dihapus.');
    }
}
