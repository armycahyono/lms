<?php

namespace App\Http\Controllers;

use App\Models\QuestionStimulus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionStimulusController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'grade_level' => ['nullable', 'integer', 'in:10,11,12'],
            'title' => ['required', 'string', 'max:255'],
            'content_html' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('question-media/stimuli', 'local')
            : null;

        QuestionStimulus::create([
            'teacher_id' => $user->id,
            'subject_id' => $validated['subject_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'title' => $validated['title'],
            'content_html' => $validated['content_html'] ?? null,
            'image_path' => $imagePath,
            'is_shared' => $request->boolean('is_shared', false),
        ]);

        return back()->with('status', 'Wacana / Stimulus berhasil ditambahkan.');
    }

    public function update(Request $request, QuestionStimulus $questionStimulus): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $questionStimulus->teacher_id === $user->id, 403);

        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'grade_level' => ['nullable', 'integer', 'in:10,11,12'],
            'title' => ['required', 'string', 'max:255'],
            'content_html' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $imagePath = $questionStimulus->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath && Storage::disk('local')->exists($imagePath)) {
                Storage::disk('local')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('question-media/stimuli', 'local');
        }

        $questionStimulus->update([
            'subject_id' => $validated['subject_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'title' => $validated['title'],
            'content_html' => $validated['content_html'] ?? null,
            'image_path' => $imagePath,
            'is_shared' => $request->boolean('is_shared', $questionStimulus->is_shared),
        ]);

        return back()->with('status', 'Wacana / Stimulus berhasil diperbarui.');
    }

    public function destroy(Request $request, QuestionStimulus $questionStimulus): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $questionStimulus->teacher_id === $user->id, 403);

        if ($questionStimulus->image_path && Storage::disk('local')->exists($questionStimulus->image_path)) {
            Storage::disk('local')->delete($questionStimulus->image_path);
        }

        $questionStimulus->delete();

        return back()->with('status', 'Wacana / Stimulus berhasil dihapus.');
    }
}
