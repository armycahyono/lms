<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\RemedialEnrichment;
use App\Services\QuestionAnalysisService;
use Illuminate\Http\JsonResponse;
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

        if ($quiz->target_remedial_only) {
            $studentId = $request->user()->id;
            $isEligible = false;

            if ($quiz->parent_quiz_id) {
                $parentQuiz = $quiz->parentQuiz;
                $passingScore = (float) ($parentQuiz?->passing_score ?? $quiz->passing_score ?? 75);
                $bestParentScore = QuizAttempt::where('quiz_id', $quiz->parent_quiz_id)
                    ->where('student_id', $studentId)
                    ->whereNotNull('submitted_at')
                    ->max('score');

                $isEligible = $bestParentScore === null || (float) $bestParentScore < $passingScore;
            } else {
                $hasPassedRemedial = RemedialEnrichment::where('course_id', $course->id)
                    ->where('student_id', $studentId)
                    ->where('type', 'remedial')
                    ->exists();
                $isEligible = $hasPassedRemedial;
            }

            if (! $isEligible) {
                return back()->withErrors(['quiz' => 'Kuis remedial ini hanya diperuntukkan bagi siswa yang nilainya masih di bawah KKTP.']);
            }
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

        $questions = $quiz->questions;
        $orderIds = is_array($attempt->question_order_json) ? $attempt->question_order_json : [];

        if ($orderIds !== []) {
            $byId = $questions->keyBy('id');
            $questions = collect($orderIds)->map(fn ($id) => $byId->get($id))->filter()->values();
        } else {
            $displayLimit = (int) ($quiz->display_question_count ?? 0);
            if ($quiz->shuffle_questions || ($displayLimit > 0 && $questions->count() > $displayLimit)) {
                $questions = $questions->shuffle($attempt->id)->values();
            }
            if ($displayLimit > 0 && $questions->count() > $displayLimit) {
                $questions = $questions->take($displayLimit)->values();
            }
            if ($questions->isNotEmpty()) {
                $attempt->update(['question_order_json' => $questions->pluck('id')->all()]);
            }
        }

        $shuffledOptionsByQuestion = [];
        if ($quiz->shuffle_options) {
            foreach ($questions as $question) {
                if (is_array($question->options_json) && $question->options_json !== []) {
                    $keys = array_keys($question->options_json);
                    mt_srand($attempt->id * 1000 + $question->id);
                    shuffle($keys);
                    mt_srand();
                    $reordered = [];
                    foreach ($keys as $k) {
                        $reordered[$k] = $question->options_json[$k];
                    }
                    $shuffledOptionsByQuestion[$question->id] = $reordered;
                }
            }
        }

        return view('quizzes.attempt', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'shuffledOptionsByQuestion' => $shuffledOptionsByQuestion,
        ]);
    }

    public function autosave(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->student_id === $request->user()->id, 403);

        if ($attempt->status !== 'in_progress') {
            return response()->json(['saved' => false, 'status' => $attempt->status], 409);
        }

        $answers = $request->input('answers', $attempt->answers_json ?? []);
        $focusLossCount = max(
            (int) $attempt->focus_loss_count,
            (int) $request->input('focus_loss_count', 0)
        );

        $attempt->update([
            'answers_json' => is_array($answers) ? $answers : [],
            'focus_loss_count' => $focusLossCount,
            'last_saved_at' => now(),
        ]);

        return response()->json([
            'saved' => true,
            'saved_at' => $attempt->last_saved_at?->format('H:i:s'),
            'focus_loss_count' => $attempt->focus_loss_count,
        ]);
    }

    public function submit(
        Request $request,
        Course $course,
        Quiz $quiz,
        QuizAttempt $attempt,
        QuestionAnalysisService $analysis
    ): RedirectResponse {
        Gate::authorize('view', $course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->student_id === $request->user()->id, 403);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('courses.quizzes.show', [$course, $quiz]);
        }

        $answers = $request->input('answers', $attempt->answers_json ?? []);
        if (! is_array($answers)) {
            $answers = [];
        }

        $quiz->load('questions');
        $orderIds = is_array($attempt->question_order_json) ? $attempt->question_order_json : [];
        $activeQuestions = $orderIds !== []
            ? $quiz->questions->whereIn('id', $orderIds)
            : $quiz->questions;

        $autoScore = 0.0;
        $hasEssay = false;
        $questionScores = [];

        foreach ($activeQuestions as $question) {
            $studentAnswer = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;

            if ($question->type === 'essay') {
                $hasEssay = true;

                continue;
            }

            $isCorrect = $analysis->isAnswerCorrect($question, $studentAnswer);
            $pts = $isCorrect === true ? (float) $question->points : 0.0;
            $questionScores[(string) $question->id] = $pts;
            $autoScore += $pts;
        }

        $focusLossCount = max(
            (int) $attempt->focus_loss_count,
            (int) $request->input('focus_loss_count', 0)
        );

        $attempt->update([
            'answers_json' => $answers,
            'question_scores_json' => $questionScores,
            'submitted_at' => now(),
            'score' => $autoScore,
            'status' => $hasEssay ? 'submitted' : 'graded',
            'focus_loss_count' => $focusLossCount,
            'last_saved_at' => now(),
        ]);

        $analysis->recordQuizAttemptStats($quiz, $attempt);

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

    public function gradeQuestion(Request $request, Course $course, Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id && $question->quiz_id === $quiz->id, 404);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.(float) $question->points],
        ]);

        $quiz->load('questions');
        $essayQuestionIds = $quiz->questions->where('type', 'essay')->pluck('id')->map(fn ($id) => (string) $id)->all();

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->whereIn('id', array_keys($validated['scores']))
            ->whereNotNull('submitted_at')
            ->get();

        foreach ($attempts as $attempt) {
            $rawScore = $validated['scores'][$attempt->id] ?? null;
            if ($rawScore === null || $rawScore === '') {
                continue;
            }

            $qScores = is_array($attempt->question_scores_json) ? $attempt->question_scores_json : [];
            $qScores[(string) $question->id] = (float) $rawScore;

            $totalScore = array_sum(array_map('floatval', $qScores));
            $allEssayGraded = collect($essayQuestionIds)->every(fn ($id) => array_key_exists($id, $qScores));

            $attempt->update([
                'question_scores_json' => $qScores,
                'score' => $totalScore,
                'status' => $allEssayGraded ? 'graded' : $attempt->status,
            ]);
        }

        return back()->with('status', 'Nilai soal esai per nomor berhasil disimpan dan total skor dihitung ulang.');
    }
}
