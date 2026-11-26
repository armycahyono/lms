<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseModuleController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        $nextOrder = ((int) $course->modules()->max('sort_order')) + 1;

        CourseModule::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $nextOrder,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('status', 'Modul pembelajaran berhasil ditambahkan.');
    }

    public function togglePublish(Course $course, CourseModule $module): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($module->course_id === $course->id, 404);

        $module->update(['is_published' => ! $module->is_published]);

        return back()->with('status', 'Status publikasi modul diperbarui.');
    }

    public function destroy(Course $course, CourseModule $module): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($module->course_id === $course->id, 404);

        $module->delete();

        return back()->with('status', 'Modul dihapus.');
    }
}
