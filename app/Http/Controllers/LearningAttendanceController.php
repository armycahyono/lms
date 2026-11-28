<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\LearningAttendance;
use App\Models\LearningSession;
use App\Models\User;
use App\Services\LmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LearningAttendanceController extends Controller
{
    public static function recordAutoDigital(Course $course, User $student, ?string $ip = null): void
    {
        if (! $student->isSiswa()) {
            return;
        }

        $openSessions = $course->learningSessions()
            ->where('is_open', true)
            ->get()
            ->filter(fn (LearningSession $session) => $session->isAcceptingCheckIn());

        foreach ($openSessions as $session) {
            $attendance = LearningAttendance::firstOrCreate(
                [
                    'learning_session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => 'hadir',
                    'source' => 'auto_digital',
                    'checked_in_at' => now(),
                ]
            );

            if ($attendance->wasRecentlyCreated) {
                ActivityLog::create([
                    'user_id' => $student->id,
                    'action' => 'learning.attendance.auto_digital',
                    'subject_type' => LearningAttendance::class,
                    'subject_id' => $attendance->id,
                    'properties_json' => ['course_id' => $course->id, 'session_id' => $session->id],
                    'ip_address' => $ip,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function storeSession(Request $request, Course $course, LmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'learning_objective_id' => ['nullable', 'integer', 'exists:learning_objectives,id'],
            'journal_summary' => ['nullable', 'string'],
            'reflection' => ['nullable', 'string'],
            'is_achieved' => ['nullable', 'boolean'],
            'session_date' => ['required', 'date'],
            'closes_at' => ['nullable', 'date'],
        ]);

        $session = LearningSession::create([
            'course_id' => $course->id,
            'teacher_id' => $request->user()->id,
            'learning_objective_id' => $validated['learning_objective_id'] ?? null,
            'title' => $validated['title'],
            'topic' => $validated['topic'] ?? null,
            'journal_summary' => $validated['journal_summary'] ?? null,
            'reflection' => $validated['reflection'] ?? null,
            'is_achieved' => $request->boolean('is_achieved', true),
            'session_date' => $validated['session_date'],
            'opens_at' => now(),
            'closes_at' => $validated['closes_at'] ?? null,
            'is_open' => true,
        ]);

        $notifications->notifyCourseStudents(
            $course,
            'attendance',
            'Sesi Pembelajaran Dibuka: '.$session->title,
            "Sesi pembelajaran digital di kelas {$course->title} telah dibuka. Kehadiran tercatat otomatis saat Anda membuka kelas.",
            ['course_id' => $course->id, 'session_id' => $session->id]
        );

        return back()->with('status', 'Jurnal & sesi pembelajaran digital berhasil dibuka.');
    }

    public function toggleSession(Course $course, LearningSession $session): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($session->course_id === $course->id, 404);

        $session->update(['is_open' => ! $session->is_open]);

        return back()->with('status', $session->is_open ? 'Presensi sesi dibuka.' : 'Presensi sesi ditutup.');
    }

    public function checkIn(Request $request, Course $course, LearningSession $session): RedirectResponse
    {
        Gate::authorize('view', $course);
        abort_unless($session->course_id === $course->id, 404);
        abort_unless($request->user()->isSiswa(), 403);

        if (! $session->isAcceptingCheckIn()) {
            return back()->withErrors(['attendance' => 'Presensi untuk sesi ini sudah ditutup.']);
        }

        $attendance = LearningAttendance::updateOrCreate(
            [
                'learning_session_id' => $session->id,
                'student_id' => $request->user()->id,
            ],
            [
                'status' => 'hadir',
                'checked_in_at' => now(),
            ]
        );

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'learning.attendance.checkin',
            'subject_type' => LearningAttendance::class,
            'subject_id' => $attendance->id,
            'properties_json' => ['course_id' => $course->id, 'session_id' => $session->id],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Kehadiran pembelajaran Anda berhasil dicatat.');
    }
}
