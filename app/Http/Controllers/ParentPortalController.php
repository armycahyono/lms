<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CbtExamResult;
use App\Models\LearningAttendance;
use App\Models\MaterialProgress;
use App\Models\QuizAttempt;
use App\Models\SchoolAttendance;
use App\Models\User;
use App\Services\SiapSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    public function index(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);

        $todayAttendance = null;
        $monthSummary = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
        $recentAttendances = collect();
        $pendingAssignments = collect();
        $overdueCount = 0;
        $recentGrades = collect();
        $courseProgress = collect();

        if ($activeChild) {
            $todayAttendance = SchoolAttendance::where('student_user_id', $activeChild->id)
                ->whereDate('attendance_date', now()->toDateString())
                ->first();

            $monthRows = SchoolAttendance::where('student_user_id', $activeChild->id)
                ->whereYear('attendance_date', now()->year)
                ->whereMonth('attendance_date', now()->month)
                ->get();

            foreach (array_keys($monthSummary) as $st) {
                $monthSummary[$st] = $monthRows->where('status', $st)->count();
            }

            $recentAttendances = SchoolAttendance::where('student_user_id', $activeChild->id)
                ->latest('attendance_date')
                ->limit(5)
                ->get();

            $yearId = $activeYear?->id;
            $courses = $activeChild->enrolledCourses()
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom'])
                ->withCount(['materials as published_materials_count' => fn ($q) => $q->where('is_published', true)])
                ->get();

            $courseIds = $courses->pluck('id')->all();
            $submittedAssignmentIds = AssignmentSubmission::where('student_id', $activeChild->id)
                ->pluck('assignment_id')
                ->all();

            $pendingAssignments = Assignment::whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->whereNotIn('id', $submittedAssignmentIds)
                ->with('course.subject')
                ->orderBy('due_at')
                ->limit(6)
                ->get();

            $overdueCount = $pendingAssignments->filter(fn ($a) => $a->due_at && $a->due_at->isPast())->count();

            $recentTaskGrades = AssignmentSubmission::where('student_id', $activeChild->id)
                ->whereNotNull('score')
                ->with('assignment.course.subject')
                ->latest('graded_at')
                ->limit(4)
                ->get()
                ->map(fn ($s) => [
                    'title' => $s->assignment?->title ?? 'Tugas',
                    'subject' => $s->assignment?->course?->subject?->name ?? '-',
                    'type' => 'Tugas',
                    'score' => (float) $s->score,
                    'date' => $s->graded_at ?? $s->submitted_at,
                ]);

            $recentQuizGrades = QuizAttempt::where('student_id', $activeChild->id)
                ->whereNotNull('submitted_at')
                ->whereNotNull('score')
                ->with('quiz.course.subject')
                ->latest('submitted_at')
                ->limit(4)
                ->get()
                ->map(fn ($q) => [
                    'title' => $q->quiz?->title ?? 'Kuis',
                    'subject' => $q->quiz?->course?->subject?->name ?? '-',
                    'type' => 'Kuis',
                    'score' => (float) $q->score,
                    'date' => $q->submitted_at,
                ]);

            $recentGrades = collect($recentTaskGrades->all())
                ->merge($recentQuizGrades->all())
                ->sortByDesc('date')
                ->take(5)
                ->values();

            $completedByCourse = MaterialProgress::where('student_id', $activeChild->id)
                ->whereNotNull('completed_at')
                ->join('materials', 'materials.id', '=', 'material_progress.material_id')
                ->selectRaw('materials.course_id, COUNT(*) as total')
                ->groupBy('materials.course_id')
                ->pluck('total', 'course_id');

            $courseProgress = $courses->map(function ($course) use ($completedByCourse): array {
                $totalMat = (int) $course->published_materials_count;
                $doneMat = (int) ($completedByCourse[$course->id] ?? 0);
                $pct = $totalMat > 0 ? min(100, (int) round(($doneMat / $totalMat) * 100)) : 0;

                return [
                    'course' => $course,
                    'done' => $doneMat,
                    'total' => $totalMat,
                    'percent' => $pct,
                ];
            });
        }

        return view('parent.index', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
            'todayAttendance' => $todayAttendance,
            'monthSummary' => $monthSummary,
            'recentAttendances' => $recentAttendances,
            'pendingAssignments' => $pendingAssignments,
            'overdueCount' => $overdueCount,
            'recentGrades' => $recentGrades,
            'courseProgress' => $courseProgress,
        ]);
    }

    public function attendance(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);
        $tab = in_array($request->query('tab'), ['summary', 'history'], true) ? $request->query('tab') : 'summary';
        $month = (string) $request->query('month', now()->format('Y-m'));

        $records = collect();
        $summary = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
        $attendancePct = 0.0;

        if ($activeChild) {
            [$yr, $mo] = array_pad(explode('-', $month), 2, null);
            $query = SchoolAttendance::where('student_user_id', $activeChild->id)
                ->when(is_numeric($yr) && is_numeric($mo), fn ($q) => $q->whereYear('attendance_date', (int) $yr)->whereMonth('attendance_date', (int) $mo))
                ->orderByDesc('attendance_date');

            $records = $query->get();
            foreach (array_keys($summary) as $st) {
                $summary[$st] = $records->where('status', $st)->count();
            }

            $total = $records->count();
            $present = $summary['hadir'] + $summary['terlambat'];
            $attendancePct = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;
        }

        return view('parent.attendance', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
            'tab' => $tab,
            'month' => $month,
            'records' => $records,
            'summary' => $summary,
            'attendancePct' => $attendancePct,
        ]);
    }

    public function records(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);
        $yearId = $activeYear?->id;

        $visitations = collect();
        $incidents = collect();
        $permits = collect();
        $bkRecords = collect();
        $followUps = collect();

        if ($activeChild) {
            $visitations = $activeChild->visitations()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->latest('planned_date')
                ->get();

            $incidents = $activeChild->incidents()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->latest('incident_date')
                ->get();

            $permits = $activeChild->permits()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->latest('permit_date')
                ->get();

            $bkRecords = $activeChild->bkRecords()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->latest('record_date')
                ->get();

            $followUps = $activeChild->homeroomFollowUps()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->latest('follow_up_date')
                ->get();
        }

        return view('parent.records', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
            'visitations' => $visitations,
            'incidents' => $incidents,
            'permits' => $permits,
            'bkRecords' => $bkRecords,
            'followUps' => $followUps,
        ]);
    }

    public function learning(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);
        $tab = in_array($request->query('tab'), ['assignments', 'grades', 'progress'], true) ? $request->query('tab') : 'assignments';

        $assignments = collect();
        $grades = collect();
        $progressRows = collect();

        if ($activeChild) {
            $courses = $activeChild->enrolledCourses()
                ->forAcademicYear($activeYear?->id)
                ->with(['subject', 'classroom'])
                ->withCount(['materials as published_materials_count' => fn ($q) => $q->where('is_published', true)])
                ->get();

            $courseIds = $courses->pluck('id')->all();
            $submissions = AssignmentSubmission::where('student_id', $activeChild->id)
                ->get()
                ->keyBy('assignment_id');

            $assignments = Assignment::whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->with('course.subject')
                ->latest('id')
                ->get()
                ->map(fn ($a) => [
                    'assignment' => $a,
                    'submission' => $submissions->get($a->id),
                ]);

            $taskGrades = AssignmentSubmission::where('student_id', $activeChild->id)
                ->whereNotNull('score')
                ->with('assignment.course.subject')
                ->get()
                ->map(fn ($s) => [
                    'title' => $s->assignment?->title ?? 'Tugas',
                    'subject' => $s->assignment?->course?->subject?->name ?? '-',
                    'category' => 'Tugas',
                    'score' => (float) $s->score,
                    'date' => $s->graded_at ?? $s->submitted_at,
                ]);

            $quizGrades = QuizAttempt::where('student_id', $activeChild->id)
                ->whereNotNull('submitted_at')
                ->with('quiz.course.subject')
                ->get()
                ->map(fn ($q) => [
                    'title' => $q->quiz?->title ?? 'Kuis',
                    'subject' => $q->quiz?->course?->subject?->name ?? '-',
                    'category' => 'Kuis / UH',
                    'score' => $q->score !== null ? (float) $q->score : null,
                    'date' => $q->submitted_at,
                ]);

            $cbtGrades = CbtExamResult::where('student_id', $activeChild->id)
                ->with('cbtExam.course.subject')
                ->get()
                ->map(fn ($c) => [
                    'title' => $c->cbtExam?->title ?? 'Ujian CBT',
                    'subject' => $c->cbtExam?->course?->subject?->name ?? '-',
                    'category' => $c->cbtExam?->exam_type ?? 'CBT',
                    'score' => $c->score !== null ? (float) $c->score : null,
                    'date' => $c->finished_at ?? $c->updated_at,
                ]);

            $grades = collect($taskGrades->all())
                ->merge($quizGrades->all())
                ->merge($cbtGrades->all())
                ->sortByDesc('date')
                ->values();

            $completedByCourse = MaterialProgress::where('student_id', $activeChild->id)
                ->whereNotNull('completed_at')
                ->join('materials', 'materials.id', '=', 'material_progress.material_id')
                ->selectRaw('materials.course_id, COUNT(*) as total')
                ->groupBy('materials.course_id')
                ->pluck('total', 'course_id');

            $digitalAttendances = LearningAttendance::where('student_id', $activeChild->id)
                ->join('learning_sessions', 'learning_sessions.id', '=', 'learning_attendances.learning_session_id')
                ->selectRaw('learning_sessions.course_id, COUNT(*) as total')
                ->groupBy('learning_sessions.course_id')
                ->pluck('total', 'course_id');

            $progressRows = $courses->map(function ($course) use ($completedByCourse, $digitalAttendances): array {
                $totalMat = (int) $course->published_materials_count;
                $doneMat = (int) ($completedByCourse[$course->id] ?? 0);

                return [
                    'course' => $course,
                    'done_materials' => $doneMat,
                    'total_materials' => $totalMat,
                    'percent' => $totalMat > 0 ? min(100, (int) round(($doneMat / $totalMat) * 100)) : 0,
                    'digital_present' => (int) ($digitalAttendances[$course->id] ?? 0),
                ];
            });
        }

        return view('parent.learning', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
            'tab' => $tab,
            'assignments' => $assignments,
            'grades' => $grades,
            'progressRows' => $progressRows,
        ]);
    }

    public function communications(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);

        $courseIds = $activeChild
            ? $activeChild->enrolledCourses()->forAcademicYear($activeYear?->id)->pluck('courses.id')->all()
            : [];

        $announcements = Announcement::query()
            ->where(function ($q) use ($courseIds): void {
                $q->whereNull('course_id');
                if ($courseIds !== []) {
                    $q->orWhereIn('course_id', $courseIds);
                }
            })
            ->with(['user', 'course.subject'])
            ->latest('is_pinned')
            ->latest('id')
            ->limit(20)
            ->get();

        $notifications = $parent->lmsNotifications()->latest('id')->limit(20)->get();

        return view('parent.communications', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
            'announcements' => $announcements,
            'notifications' => $notifications,
        ]);
    }

    public function profile(Request $request): View
    {
        [$parent, $children, $activeChild, $activeYear] = $this->resolveContext($request);

        return view('parent.profile', [
            'parent' => $parent,
            'children' => $children,
            'activeChild' => $activeChild,
            'activeYear' => $activeYear,
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $parent = $request->user();
        abort_unless($parent->isOrangTua(), 403);

        $validated = $request->validate([
            'parent_notify_mode' => ['required', 'in:all,alerts_only'],
        ]);

        $parent->update([
            'parent_notify_mode' => $validated['parent_notify_mode'],
        ]);

        return back()->with('status', 'Preferensi notifikasi orang tua berhasil diperbarui.');
    }

    public function resyncAttendance(Request $request, SiapSyncService $sync): RedirectResponse
    {
        [$parent, $children, $activeChild] = $this->resolveContext($request);

        if (! $activeChild) {
            return back()->withErrors(['attendance' => 'Belum ada data anak yang terhubung.']);
        }

        $count = $sync->resyncSchoolAttendances(now()->toDateString(), $activeChild->username);

        return back()->with('status', "Sinkronisasi presensi SIAP selesai ({$count} data diperbarui).");
    }

    /**
     * @return array{0: User, 1: Collection<int, User>, 2: User|null, 3: AcademicYear|null}
     */
    private function resolveContext(Request $request): array
    {
        $parent = $request->user();
        abort_unless($parent->isOrangTua(), 403);

        $children = $parent->children()
            ->with(['classrooms.academicYear', 'profile'])
            ->orderBy('name')
            ->get();

        if ($request->filled('child_id')) {
            $requestedId = (int) $request->query('child_id');
            if ($children->contains('id', $requestedId)) {
                $request->session()->put('parent_active_child_id', $requestedId);
            }
        }

        $activeChild = $children->firstWhere('id', (int) $request->session()->get('parent_active_child_id'))
            ?? $children->first();

        $activeYear = AcademicYear::selectedFor($parent);

        return [$parent, $children, $activeChild, $activeYear];
    }
}
