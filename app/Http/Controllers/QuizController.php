<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_published' => ['boolean'],
        ]);

        Quiz::create([
            'course_id' => $course->id,
            'module_id' => $validated['module_id'] ?? null,
            'title' => $validated['title'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_attempts' => $validated['max_attempts'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('status', 'Kuis harian berhasil dibuat.');
    }

    public function show(Request $request, Course $course, Quiz $quiz): View
    {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $canManage = Gate::allows('manage', $course);
        abort_unless($canManage || $quiz->is_published, 403);

        $quiz->load('questions');

        $attempts = $canManage
            ? $quiz->attempts()->with('student')->latest('id')->get()
            : $quiz->attempts()->where('student_id', $request->user()->id)->latest('id')->get();

        return view('quizzes.show', [
            'course' => $course,
            'quiz' => $quiz,
            'canManage' => $canManage,
            'attempts' => $attempts,
        ]);
    }

    public function storeQuestion(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $validated = $request->validate([
            'type' => ['required', 'in:multiple_choice,essay'],
            'question_text' => ['required', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:100'],
        ]);

        $options = null;
        if ($validated['type'] === 'multiple_choice') {
            $options = array_filter([
                'A' => $validated['option_a'] ?? null,
                'B' => $validated['option_b'] ?? null,
                'C' => $validated['option_c'] ?? null,
                'D' => $validated['option_d'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        $nextOrder = ((int) $quiz->questions()->max('sort_order')) + 1;

        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'options_json' => $options,
            'correct_answer' => $validated['correct_answer'] ?? null,
            'points' => $validated['points'],
            'sort_order' => $nextOrder,
        ]);

        return back()->with('status', 'Soal kuis berhasil ditambahkan.');
    }

    public function togglePublish(Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('status', 'Status publikasi kuis diperbarui.');
    }
}
