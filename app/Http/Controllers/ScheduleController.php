<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Quiz;
use App\Models\SchoolCalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $monthParam = (string) $request->query('month', now()->format('Y-m'));

        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } catch (\Throwable) {
            $currentMonth = now()->startOfMonth();
        }

        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        $yearId = AcademicYear::selectedFor($user)?->id;

        if ($user->isSiswa()) {
            $courses = $user->enrolledCourses()
                ->where('courses.status', 'active')
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom', 'teachers'])
                ->get();
        } elseif ($user->isGuru()) {
            $courses = $user->teachingCourses()
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom', 'teachers'])
                ->get();
        } else {
            $courses = Course::query()
                ->where('status', 'active')
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom', 'teachers'])
                ->get();
        }

        $courseIds = $courses->pluck('id')->all();

        $schedulesByDay = CourseSchedule::query()
            ->whereIn('course_id', $courseIds)
            ->with(['course.subject', 'course.classroom', 'course.teachers'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $assignments = Assignment::query()
            ->whereIn('course_id', $courseIds)
            ->when($user->isSiswa(), fn ($q) => $q->where('is_published', true))
            ->whereBetween('due_at', [$monthStart, $monthEnd])
            ->with('course.subject')
            ->orderBy('due_at')
            ->get();

        $quizzes = Quiz::query()
            ->whereIn('course_id', $courseIds)
            ->when($user->isSiswa(), fn ($q) => $q->where('is_published', true))
            ->where(function ($q) use ($monthStart, $monthEnd): void {
                $q->whereBetween('start_at', [$monthStart, $monthEnd])
                    ->orWhereBetween('end_at', [$monthStart, $monthEnd]);
            })
            ->with('course.subject')
            ->get();

        $calendarEvents = collect();

        foreach ($assignments as $assignment) {
            $dateKey = $assignment->due_at->format('Y-m-d');
            $calendarEvents->push([
                'date' => $dateKey,
                'time' => $assignment->due_at->format('H:i'),
                'type' => 'assignment',
                'title' => $assignment->title,
                'subtitle' => $assignment->course?->subject?->name ?? $assignment->course?->title,
                'url' => route('courses.assignments.show', [$assignment->course_id, $assignment]),
            ]);
        }

        foreach ($quizzes as $quiz) {
            $targetDate = $quiz->start_at ?? $quiz->end_at;
            if ($targetDate) {
                $calendarEvents->push([
                    'date' => $targetDate->format('Y-m-d'),
                    'time' => $targetDate->format('H:i'),
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'subtitle' => $quiz->course?->subject?->name ?? $quiz->course?->title,
                    'url' => route('courses.quizzes.show', [$quiz->course_id, $quiz]),
                ]);
            }
        }

        $schoolEvents = SchoolCalendarEvent::query()
            ->when($yearId !== null, fn ($q) => $q->where(fn ($sub) => $sub->where('academic_year_id', $yearId)->orWhereNull('academic_year_id')))
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate(\DB::raw('COALESCE(end_date, start_date)'), '>=', $monthStart->toDateString())
            ->orderBy('start_date')
            ->get();

        foreach ($schoolEvents as $schoolEvent) {
            $rangeStart = $schoolEvent->start_date->copy()->max($monthStart);
            $rangeEnd = ($schoolEvent->end_date ?? $schoolEvent->start_date)->copy()->min($monthEnd);

            for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
                $calendarEvents->push([
                    'date' => $cursor->format('Y-m-d'),
                    'time' => $schoolEvent->is_all_day ? 'Seharian' : ($schoolEvent->start_time ?? '00:00'),
                    'type' => $schoolEvent->is_holiday ? 'holiday' : 'school_event',
                    'title' => $schoolEvent->title,
                    'subtitle' => $schoolEvent->category_label ?? 'Kalender Kegiatan Sekolah',
                    'url' => '#agenda-sekolah',
                ]);
            }
        }

        $eventsByDate = $calendarEvents->sortBy('time')->groupBy('date');

        return view('schedule.index', [
            'courses' => $courses,
            'schedulesByDay' => $schedulesByDay,
            'dayNames' => CourseSchedule::dayNames(),
            'currentMonth' => $currentMonth,
            'eventsByDate' => $eventsByDate,
            'calendarEvents' => $calendarEvents->sortBy(['date', 'time'])->values(),
            'canManageSchedules' => $user->isGuru() || $user->isAdmin(),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        CourseSchedule::create([
            'course_id' => $course->id,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $validated['room'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Jadwal pembelajaran berhasil ditambahkan.');
    }

    public function destroy(CourseSchedule $schedule): RedirectResponse
    {
        Gate::authorize('manage', $schedule->course);

        $schedule->delete();

        return back()->with('status', 'Jadwal pembelajaran berhasil dihapus.');
    }
}
