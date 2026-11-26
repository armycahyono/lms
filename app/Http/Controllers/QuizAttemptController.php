<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function start(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id && $quiz->is_published, 404);
        abort_unless($request->user()->isSiswa(), 403);

        if ($quiz->start_at !== null && now()->lessThan($quiz->start_at)) {
            return back()->withErrors(['quiz' => 'Kuis belum dibuka.']);
        }

        if ($quiz->end_at !== null && now()->greaterThan($quiz->end_at)) {
            return back()->withErrors(['quiz' => 'Waktu pelaksanaan kuis telah berakhir.']);
        }

        $inProgress = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return redirect()->route('courses.quizzes.attempts.show', [$course, $quiz, $inProgress]);
        }

        $usedAttempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->count();

        if ($usedAttempts >= $quiz->max_attempts) {
            return back()->withErrors(['quiz' => 'Batas percobaan kuis telah tercapai.']);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $request->user()->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('courses.quizzes.attempts.show', [$course, $quiz, $attempt]);
    }

    public function show(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->student_id === $request->user()->id || Gate::allows('manage', $course), 403);

        $quiz->load('questions');

        return view('quizzes.attempt', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->student_id === $request->user()->id, 403);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('courses.quizzes.show', [$course, $quiz]);
        }

        $answers = $request->input('answers', []);
        $quiz->load('questions');

        $autoScore = 0.0;
        $hasEssay = false;

        foreach ($quiz->questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;

            if ($question->type === 'multiple_choice') {
                if (is_string($studentAnswer) && strcasecmp(trim($studentAnswer), trim((string) $question->correct_answer)) === 0) {
                    $autoScore += (float) $question->points;
                }
            } else {
                $hasEssay = true;
            }
        }

        $attempt->update([
            'answers_json' => $answers,
            'submitted_at' => now(),
            'score' => $autoScore,
            'status' => $hasEssay ? 'submitted' : 'graded',
        ]);

        return redirect()->route('courses.quizzes.show', [$course, $quiz])
            ->with('status', 'Jawaban kuis berhasil dikirim.');
    }

    public function grade(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $attempt->update([
            'score' => $validated['score'],
            'status' => 'graded',
        ]);

        return back()->with('status', 'Skor akhir kuis berhasil disimpan.');
    }
}
