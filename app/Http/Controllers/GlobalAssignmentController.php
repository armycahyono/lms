<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\LmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GlobalAssignmentController extends Controller
{
    public function index(Request $request, string $defaultTab = 'tasks'): View
    {
        $user = $request->user();
        $filter = (string) $request->query('filter', 'all');
        $activeTab = (string) $request->query('tab', $defaultTab);
        if (! in_array($activeTab, ['tasks', 'quizzes'], true)) {
            $activeTab = 'tasks';
        }

        $yearId = AcademicYear::selectedFor($user)?->id;
        [$assignments, $quizzes, $mode, $manageableCourses] = $this->loadDataForUser($user, $filter, $yearId);

        $coursesBySubjectGrade = $manageableCourses->groupBy(function (Course $c): string {
            $grade = $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : '';

            return ($c->subject_id ?? 0).'|'.$grade;
        });

        $assignmentGroups = $mode === 'student' ? collect() : $assignments->groupBy(function (Assignment $a): string {
            $grade = $a->course?->classroom
                ? Classroom::parseGradeNumber($a->course->classroom->grade_level, $a->course->classroom->name)
                : '';

            return implode('|', [
                mb_strtolower(trim($a->title)),
                (string) ($a->assessment_type ?? 'formatif'),
                (string) ($a->course?->subject_id ?? 0),
                (string) $grade,
            ]);
        })->map(function ($group) use ($coursesBySubjectGrade): array {
            /** @var Assignment $first */
            $first = $group->first();
            $subjectId = $first->course?->subject_id ?? 0;
            $grade = $first->course?->classroom
                ? Classroom::parseGradeNumber($first->course->classroom->grade_level, $first->course->classroom->name)
                : null;
            $sgKey = $subjectId.'|'.($grade ?? '');
            $expectedCourses = $coursesBySubjectGrade->get($sgKey, collect());
            $coveredCourseIds = $group->pluck('course_id')->unique();
            $missingCourses = $expectedCourses->filter(fn (Course $c): bool => ! $coveredCourseIds->contains($c->id))->values();

            return [
                'first_assignment' => $first,
                'assignments' => $group,
                'title' => $first->title,
                'assessment_type' => $first->assessment_type ?? 'formatif',
                'subject_id' => $subjectId,
                'subject_name' => $first->course?->subject?->name ?? $first->course?->title ?? 'Mata Pelajaran',
                'grade_level' => $grade,
                'due_at' => $first->due_at,
                'total_submissions' => $group->sum('submissions_count'),
                'ungraded_submissions_count' => $group->sum('ungraded_submissions_count'),
                'covered_count' => $coveredCourseIds->count(),
                'expected_count' => max($expectedCourses->count(), $coveredCourseIds->count()),
                'missing_courses' => $missingCourses,
            ];
        })->values();

        $filterSubjects = $manageableCourses->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $filterGrades = $manageableCourses->map(function (Course $c): ?int {
            return $c->classroom
                ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                : null;
        })->filter()->unique()->sort()->values();

        $assignmentSubjectGradeGroups = $mode === 'student' ? collect() : $assignmentGroups
            ->groupBy(fn (array $pkg): string => $pkg['subject_id'].'|'.($pkg['grade_level'] ?? ''))
            ->map(function ($items): array {
                $first = $items->first();

                return [
                    'subject_id' => $first['subject_id'],
                    'subject_name' => $first['subject_name'],
                    'grade_level' => $first['grade_level'],
                    'packages' => $items->values(),
                ];
            })
            ->sortBy([
                ['grade_level', 'asc'],
                ['subject_name', 'asc'],
            ])
            ->values();

        $assignmentGradeGroups = $mode === 'student' ? collect() : $assignmentSubjectGradeGroups
            ->groupBy(fn (array $grp): string => (string) ($grp['grade_level'] ?? 'other'))
            ->map(function ($subjects): array {
                $first = $subjects->first();

                return [
                    'grade_level' => $first['grade_level'],
                    'subjects' => $subjects->values(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        return view('tasks.index', [
            'assignments' => $assignments,
            'assignmentGroups' => $assignmentGroups,
            'assignmentSubjectGradeGroups' => $assignmentSubjectGradeGroups,
            'assignmentGradeGroups' => $assignmentGradeGroups,
            'quizzes' => $quizzes,
            'filter' => $filter,
            'activeTab' => $activeTab,
            'mode' => $mode,
            'manageableCourses' => $manageableCourses,
            'filterSubjects' => $filterSubjects,
            'filterGrades' => $filterGrades,
        ]);
    }

    public function storeBatchAssignment(Request $request, LmsNotificationService $notifications): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'assessment_type' => ['nullable', 'in:diagnostik,formatif,sumatif'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'rubric_notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $courses = Course::whereIn('id', $validated['course_ids'])->get();
        foreach ($courses as $course) {
            Gate::authorize('manage', $course);
        }

        $tempFile = $request->hasFile('attachment')
            ? $request->file('attachment')->store('assignments/tmp', 'local')
            : null;

        foreach ($courses as $course) {
            $attachmentPath = null;
            if ($tempFile && Storage::disk('local')->exists($tempFile)) {
                $attachmentPath = "assignments/{$course->id}/".uniqid('task_', true).'_'.basename($tempFile);
                Storage::disk('local')->copy($tempFile, $attachmentPath);
            }

            $assignment = Assignment::create([
                'course_id' => $course->id,
                'assessment_type' => $validated['assessment_type'] ?? 'formatif',
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'rubric_notes' => $validated['rubric_notes'] ?? null,
                'attachment_path' => $attachmentPath,
                'due_at' => $validated['due_at'] ?? null,
                'max_score' => $validated['max_score'],
                'allow_late' => true,
                'is_published' => true,
            ]);

            $dueInfo = $assignment->due_at ? ' Tenggat: '.$assignment->due_at->format('d M Y H:i') : '';
            $notifications->notifyCourseStudents(
                $course,
                'assignment',
                'Tugas Baru: '.$assignment->title,
                "Tugas baru di kelas {$course->title}.{$dueInfo}",
                ['course_id' => $course->id, 'assignment_id' => $assignment->id]
            );
        }

        if ($tempFile && Storage::disk('local')->exists($tempFile)) {
            Storage::disk('local')->delete($tempFile);
        }

        return redirect()->route('tasks.index', ['tab' => 'tasks'])
            ->with('status', "Tugas berhasil dibuat untuk {$courses->count()} kelas.");
    }

    public function storeBatchQuiz(Request $request, LmsNotificationService $notifications): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'assessment_type' => ['nullable', 'in:diagnostik,formatif,sumatif'],
            'quiz_type' => ['nullable', 'in:reguler,remedial,pengayaan'],
            'target_remedial_only' => ['nullable', 'boolean'],
            'parent_quiz_id' => ['nullable', 'integer', 'exists:quizzes,id'],
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'display_question_count' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $courses = Course::whereIn('id', $validated['course_ids'])->get();
        foreach ($courses as $course) {
            Gate::authorize('manage', $course);
        }

        $createdQuiz = null;
        $firstCourse = null;

        foreach ($courses as $course) {
            $quiz = Quiz::create([
                'course_id' => $course->id,
                'assessment_type' => $validated['assessment_type'] ?? 'formatif',
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
                'is_published' => true,
            ]);

            $notifications->notifyCourseStudents(
                $course,
                'quiz',
                'Kuis Baru: '.$quiz->title,
                "Kuis harian baru tersedia di kelas {$course->title}.",
                ['course_id' => $course->id, 'quiz_id' => $quiz->id]
            );

            $createdQuiz ??= $quiz;
            $firstCourse ??= $course;
        }

        if ($courses->count() === 1 && $createdQuiz && $firstCourse) {
            return redirect()->route('courses.quizzes.show', [$firstCourse, $createdQuiz])
                ->with('status', 'Kuis berhasil dibuat. Silakan tambah atau impor soal dari Bank Soal.');
        }

        return redirect()->route('exams.index', ['tab' => 'quizzes'])
            ->with('status', "Kuis berhasil dibuat untuk {$courses->count()} kelas.");
    }

    /**
     * @return array{0: Collection<int, Assignment>, 1: Collection<int, Quiz>, 2: string, 3: Collection<int, Course>}
     */
    private function loadDataForUser($user, string $filter, ?int $yearId): array
    {
        if ($user->isSiswa()) {
            $assignments = Assignment::query()
                ->where('is_published', true)
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->where('status', 'active')
                        ->forAcademicYear($yearId)
                        ->whereHas('students', fn ($sq) => $sq->where('users.id', $user->id));
                })
                ->with(['course.subject', 'course.classroom', 'submissions' => fn ($q) => $q->where('student_id', $user->id)])
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at ASC')
                ->get();

            if ($filter === 'pending') {
                $assignments = $assignments->filter(fn (Assignment $a): bool => $a->submissions->isEmpty())->values();
            } elseif ($filter === 'submitted') {
                $assignments = $assignments->filter(fn (Assignment $a): bool => $a->submissions->isNotEmpty())->values();
            }

            $quizzes = Quiz::query()
                ->where('is_published', true)
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->where('status', 'active')
                        ->forAcademicYear($yearId)
                        ->whereHas('students', fn ($sq) => $sq->where('users.id', $user->id));
                })
                ->with(['course.subject', 'course.classroom', 'attempts' => fn ($q) => $q->where('student_id', $user->id)->latest()])
                ->withCount('questions')
                ->orderByRaw('CASE WHEN end_at IS NULL THEN 1 ELSE 0 END, end_at ASC')
                ->get();

            return [$assignments, $quizzes, 'student', collect()];
        }

        if ($user->isGuru()) {
            $assignments = Assignment::query()
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->with(['course.subject', 'course.classroom'])
                ->withCount(['submissions', 'submissions as ungraded_submissions_count' => fn ($q) => $q->whereNull('graded_at')])
                ->latest()
                ->get();

            if ($filter === 'ungraded') {
                $assignments = $assignments->filter(fn (Assignment $a): bool => $a->ungraded_submissions_count > 0)->values();
            }

            $quizzes = Quiz::query()
                ->whereHas('course', function ($q) use ($user, $yearId): void {
                    $q->forAcademicYear($yearId)
                        ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                })
                ->with(['course.subject', 'course.classroom'])
                ->withCount(['questions', 'attempts'])
                ->latest()
                ->get();

            $manageableCourses = $user->teachingCourses()
                ->forAcademicYear($yearId)
                ->with(['classroom', 'subject'])
                ->orderBy('title')
                ->get();

            return [$assignments, $quizzes, 'teacher', $manageableCourses];
        }

        $assignments = Assignment::query()
            ->whereHas('course', fn ($q) => $q->forAcademicYear($yearId))
            ->with(['course.subject', 'course.classroom'])
            ->withCount(['submissions', 'submissions as ungraded_submissions_count' => fn ($q) => $q->whereNull('graded_at')])
            ->latest()
            ->get();

        $quizzes = Quiz::query()
            ->whereHas('course', fn ($q) => $q->forAcademicYear($yearId))
            ->with(['course.subject', 'course.classroom'])
            ->withCount(['questions', 'attempts'])
            ->latest()
            ->get();

        $manageableCourses = Course::forAcademicYear($yearId)
            ->with(['classroom', 'subject'])
            ->orderBy('title')
            ->get();

        return [$assignments, $quizzes, 'admin', $manageableCourses];
    }
}
