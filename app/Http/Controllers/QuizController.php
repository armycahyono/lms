<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\LmsNotificationService;
use App\Services\QuestionAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function store(Request $request, Course $course, LmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'assessment_type' => ['nullable', 'in:diagnostik,formatif,sumatif'],
            'learning_objective_id' => ['nullable', 'integer', 'exists:learning_objectives,id'],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'display_question_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'quiz_type' => ['nullable', 'in:reguler,remedial,pengayaan'],
            'target_remedial_only' => ['nullable', 'boolean'],
            'parent_quiz_id' => ['nullable', 'integer', 'exists:quizzes,id'],
            'is_published' => ['boolean'],
        ]);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'assessment_type' => $validated['assessment_type'] ?? 'formatif',
            'learning_objective_id' => $validated['learning_objective_id'] ?? null,
            'module_id' => $validated['module_id'] ?? null,
            'title' => $validated['title'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_attempts' => $validated['max_attempts'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'display_question_count' => $validated['display_question_count'] ?? null,
            'passing_score' => $validated['passing_score'] ?? 75,
            'quiz_type' => $validated['quiz_type'] ?? 'reguler',
            'target_remedial_only' => $request->boolean('target_remedial_only', ($validated['quiz_type'] ?? 'reguler') === 'remedial'),
            'parent_quiz_id' => $validated['parent_quiz_id'] ?? null,
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_published' => $request->boolean('is_published', true),
        ]);

        if ($quiz->is_published) {
            $notifications->notifyCourseStudents(
                $course,
                'quiz',
                'Kuis Baru: '.$quiz->title,
                "Kuis baru tersedia di kelas {$course->title} (durasi {$quiz->duration_minutes} menit).",
                ['course_id' => $course->id, 'quiz_id' => $quiz->id]
            );
        }

        return back()->with('status', 'Kuis harian berhasil dibuat.');
    }

    public function destroy(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['quiz' => 'Kuis tidak dapat dihapus karena sudah ada siswa yang mengerjakan.']);
        }

        if ($quiz->is_published && ! $request->user()->isAdmin()) {
            return back()->withErrors(['quiz' => 'Kuis yang sudah dirilis/dipublikasikan tidak dapat dihapus oleh guru.']);
        }

        $quiz->delete();

        return redirect()->route('courses.show', $course)->with('status', 'Kuis berhasil dihapus.');
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

        $bankQuestions = $canManage
            ? QuestionBank::query()
                ->where(function ($q) use ($request, $course): void {
                    $q->where('teacher_id', $request->user()->id)
                        ->orWhere(function ($shared) use ($course): void {
                            $shared->where('is_shared', true)->where('subject_id', $course->subject_id);
                        });
                })
                ->with('teacher')
                ->latest('id')
                ->limit(50)
                ->get()
            : collect();

        $analytics = null;
        $itemAnalysis = collect();
        if ($canManage) {
            $finishedAttempts = $attempts->filter(fn ($a) => $a->submitted_at !== null && $a->score !== null);
            $passing = (float) ($quiz->passing_score ?? 75);
            $passedCount = $finishedAttempts->filter(fn ($a) => (float) $a->score >= $passing)->count();

            $analytics = [
                'submitted_count' => $finishedAttempts->count(),
                'avg_score' => $finishedAttempts->isNotEmpty() ? round((float) $finishedAttempts->avg('score'), 1) : 0,
                'max_score' => $finishedAttempts->isNotEmpty() ? (float) $finishedAttempts->max('score') : 0,
                'min_score' => $finishedAttempts->isNotEmpty() ? (float) $finishedAttempts->min('score') : 0,
                'passed_count' => $passedCount,
                'remedial_count' => max(0, $finishedAttempts->count() - $passedCount),
            ];

            if ($finishedAttempts->isNotEmpty()) {
                $itemAnalysis = $quiz->questions->mapWithKeys(function (QuizQuestion $question) use ($finishedAttempts) {
                    $answered = 0;
                    $correct = 0;
                    $expected = strtoupper(trim((string) $question->correct_answer));

                    foreach ($finishedAttempts as $attempt) {
                        $answers = $attempt->answers_json ?? [];
                        $given = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;
                        if ($given !== null && $given !== '') {
                            $answered++;
                            if ($expected !== '' && strtoupper(trim((string) $given)) === $expected) {
                                $correct++;
                            }
                        }
                    }

                    $pct = $finishedAttempts->count() > 0 ? round(($correct / $finishedAttempts->count()) * 100, 1) : 0;
                    $difficultyLabel = $pct >= 70 ? 'Mudah' : ($pct >= 30 ? 'Sedang' : 'Sulit');

                    return [$question->id => [
                        'answered' => $answered,
                        'correct' => $correct,
                        'correct_pct' => $pct,
                        'difficulty_label' => $difficultyLabel,
                    ]];
                });
            }
        }

        return view('quizzes.show', [
            'course' => $course,
            'quiz' => $quiz,
            'canManage' => $canManage,
            'attempts' => $attempts,
            'bankQuestions' => $bankQuestions,
            'analytics' => $analytics,
            'itemAnalysis' => $itemAnalysis,
        ]);
    }

    public function importFromBank(Request $request, Course $course, Quiz $quiz, QuestionAnalysisService $analysis): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $validated = $request->validate([
            'question_bank_ids' => ['required', 'array', 'min:1'],
            'question_bank_ids.*' => ['integer', 'exists:question_banks,id'],
        ]);

        $user = $request->user();
        $items = QuestionBank::whereIn('id', $validated['question_bank_ids'])->get();
        foreach ($items as $item) {
            $allowed = $user->isAdmin()
                || $item->teacher_id === $user->id
                || ($item->is_shared && $item->subject_id === $course->subject_id);
            abort_unless($allowed, 403);
        }
        $nextOrder = ((int) $quiz->questions()->max('sort_order'));

        foreach ($items as $item) {
            $nextOrder++;
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_bank_id' => $item->id,
                'type' => $item->type,
                'question_text' => $item->question_text,
                'question_image' => $item->question_image,
                'options_json' => $item->options_json,
                'option_images_json' => $item->option_images_json,
                'correct_answer' => $item->correct_answer,
                'points' => $item->points,
                'sort_order' => $nextOrder,
            ]);
        }

        $analysis->incrementUsageCount($items->pluck('id')->all());

        return redirect()->route('courses.quizzes.show', [$course, $quiz])
            ->with('status', count($items).' soal dari Bank Soal berhasil ditambahkan ke kuis.');
    }

    public function pickQuestions(Request $request, Course $course, Quiz $quiz): View
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $course->loadMissing(['subject', 'classroom']);
        $quiz->loadMissing('questions');

        $user = $request->user();
        $courseSubjectId = $course->subject_id;
        $courseGradeLevel = $course->classroom
            ? Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name)
            : null;

        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $difficulty = trim((string) $request->query('difficulty', ''));
        $gradeLevel = $request->query('grade_level');
        $department = trim((string) $request->query('department', ''));
        $visibility = trim((string) $request->query('visibility', ''));
        $category = trim((string) $request->query('category', ''));

        $attachedBankIds = $quiz->questions->pluck('question_bank_id')->filter()->all();

        $questions = QuestionBank::query()
            ->when($attachedBankIds !== [], fn ($q) => $q->whereNotIn('id', $attachedBankIds))
            ->where(function ($q) use ($user, $courseSubjectId, $courseGradeLevel): void {
                $q->where(function ($own) use ($user, $courseSubjectId): void {
                    $own->where('teacher_id', $user->id);
                    if ($courseSubjectId) {
                        $own->where(fn ($s) => $s->where('subject_id', $courseSubjectId)->orWhereNull('subject_id'));
                    }
                })->orWhere(function ($shared) use ($courseSubjectId, $courseGradeLevel): void {
                    $shared->where('is_shared', true);
                    if ($courseSubjectId) {
                        $shared->where('subject_id', $courseSubjectId);
                    }
                    if ($courseGradeLevel) {
                        $shared->where(fn ($g) => $g->where('grade_level', $courseGradeLevel)->orWhereNull('grade_level'));
                    }
                });
            })
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('question_text', 'like', "%{$search}%")
                    ->orWhere('kisi_indicator', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($difficulty !== '', fn ($q) => $q->where('difficulty', $difficulty))
            ->when($gradeLevel, fn ($q) => $q->where('grade_level', (int) $gradeLevel))
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->when($category !== '', fn ($q) => $q->where('category', 'like', "%{$category}%"))
            ->when($visibility === 'private', fn ($q) => $q->where('teacher_id', $user->id)->where('is_shared', false))
            ->when($visibility === 'shared', fn ($q) => $q->where('is_shared', true))
            ->with(['teacher', 'subject'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $departments = Classroom::query()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->unique()
            ->values();

        return view('question-bank.picker', [
            'targetTitle' => $quiz->title,
            'targetSubtitle' => ($course->subject?->name ?? 'Mapel').' · '.($course->classroom?->name ?? '-').' (Kuis / UH)',
            'existingCount' => $quiz->questions->count(),
            'backUrl' => route('courses.quizzes.show', [$course, $quiz]),
            'submitUrl' => route('courses.quizzes.import-bank', [$course, $quiz]),
            'questions' => $questions,
            'departments' => $departments,
            'gradeLevels' => $courseGradeLevel ? [$courseGradeLevel] : [10, 11, 12],
            'filters' => [
                'q' => $search,
                'type' => $type,
                'difficulty' => $difficulty,
                'grade_level' => $gradeLevel,
                'department' => $department,
                'visibility' => $visibility,
                'category' => $category,
            ],
        ]);
    }

    public function storeQuestion(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $validated = $request->validate([
            'type' => ['required', 'in:multiple_choice,multiple_choice_complex,matching,short_answer,essay'],
            'question_text' => ['required', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'option_e' => ['nullable', 'string'],
            'matching_pairs' => ['nullable', 'string'],
            'correct_answer' => ['nullable', 'string'],
            'rubric_keywords' => ['nullable', 'string', 'max:500'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'save_to_bank' => ['nullable', 'boolean'],
        ]);

        $options = null;
        $correctAnswer = $validated['correct_answer'] ?? null;

        if (in_array($validated['type'], ['multiple_choice', 'multiple_choice_complex'], true)) {
            $options = array_filter([
                'A' => $validated['option_a'] ?? null,
                'B' => $validated['option_b'] ?? null,
                'C' => $validated['option_c'] ?? null,
                'D' => $validated['option_d'] ?? null,
                'E' => $validated['option_e'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        } elseif ($validated['type'] === 'matching') {
            $pairs = [];
            foreach (explode('|', (string) ($validated['matching_pairs'] ?? '')) as $idx => $pairStr) {
                $parts = explode('=', $pairStr, 2);
                if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                    $pairs[(string) ($idx + 1)] = [
                        'left' => trim($parts[0]),
                        'right' => trim($parts[1]),
                    ];
                }
            }
            $options = $pairs !== [] ? $pairs : null;
            if ($pairs !== []) {
                $correctAnswer = json_encode(collect($pairs)->mapWithKeys(fn ($p) => [$p['left'] => $p['right']])->all(), JSON_UNESCAPED_UNICODE);
            }
        }

        $saveToBank = $request->has('save_to_bank') ? $request->boolean('save_to_bank') : true;

        DB::transaction(function () use ($request, $course, $quiz, $validated, $options, $correctAnswer, $saveToBank): void {
            $bankQuestion = null;
            if ($saveToBank) {
                $course->loadMissing('classroom');
                $gradeLevel = $course->classroom
                    ? Classroom::parseGradeNumber($course->classroom->grade_level, $course->classroom->name)
                    : null;

                $bankQuestion = QuestionBank::create([
                    'teacher_id' => $request->user()->id,
                    'subject_id' => $course->subject_id,
                    'grade_level' => $gradeLevel,
                    'difficulty' => 'sedang',
                    'type' => $validated['type'],
                    'question_text' => $validated['question_text'],
                    'options_json' => $options ?? [],
                    'correct_answer' => $correctAnswer,
                    'points' => $validated['points'],
                    'is_shared' => false,
                    'usage_count' => 1,
                    'last_used_at' => now(),
                ]);
            }

            $nextOrder = ((int) $quiz->questions()->max('sort_order')) + 1;

            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_bank_id' => $bankQuestion?->id,
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'options_json' => $options,
                'correct_answer' => $correctAnswer,
                'rubric_keywords' => $validated['rubric_keywords'] ?? null,
                'points' => $validated['points'],
                'sort_order' => $nextOrder,
            ]);
        });

        return back()->with('status', $saveToBank
            ? 'Soal kuis berhasil ditambahkan dan disimpan ke Bank Soal.'
            : 'Soal kuis berhasil ditambahkan.');
    }

    public function gradeByQuestion(
        Request $request,
        Course $course,
        Quiz $quiz,
        QuizQuestion $question,
        QuestionAnalysisService $analysis
    ): View {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id && $question->quiz_id === $quiz->id, 404);

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->with('student')
            ->orderBy('id')
            ->get()
            ->map(function (QuizAttempt $attempt) use ($question, $analysis): array {
                $answers = $attempt->answers_json ?? [];
                $rawAnswer = (string) ($answers[$question->id] ?? $answers[(string) $question->id] ?? '');
                $qScores = $attempt->question_scores_json ?? [];
                $currentScore = $qScores[(string) $question->id] ?? null;

                return [
                    'attempt' => $attempt,
                    'raw_answer' => $rawAnswer,
                    'highlighted_html' => $analysis->highlightKeywords($rawAnswer, $question->rubric_keywords ?: $question->correct_answer),
                    'current_score' => $currentScore,
                ];
            });

        return view('quizzes.grade-by-question', [
            'course' => $course,
            'quiz' => $quiz,
            'question' => $question,
            'rows' => $attempts,
        ]);
    }

    public function togglePublish(Course $course, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('status', 'Status publikasi kuis diperbarui.');
    }
}
