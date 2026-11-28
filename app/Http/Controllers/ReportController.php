<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $realActiveYearId = AcademicYear::active()?->id;

        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $realActiveYearId)
            || $user->hasStructuralRole('waka_kurikulum', $realActiveYearId);

        abort_unless($isLeadership || $user->isGuru(), 403);

        $courses = $this->scopedCoursesQuery($user, $isLeadership, $activeYear?->id)->get();

        $reportGradeGroups = $courses
            ->groupBy(function (Course $c): string {
                $grade = $c->classroom
                    ? Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name)
                    : null;

                return (string) ($grade ?? 'other');
            })
            ->map(function ($byGrade): array {
                $first = $byGrade->first();
                $grade = $first->classroom
                    ? Classroom::parseGradeNumber($first->classroom->grade_level, $first->classroom->name)
                    : null;

                $subjects = $byGrade->groupBy(fn (Course $c): string => (string) ($c->subject_id ?? 0))
                    ->map(function ($bySubject): array {
                        /** @var Course $sample */
                        $sample = $bySubject->first();

                        return [
                            'subject_name' => $sample->subject?->name ?? 'Umum / Tanpa Mapel',
                            'courses' => $bySubject->sortBy(fn (Course $c) => $c->classroom?->name ?? $c->title)->values(),
                        ];
                    })
                    ->sortBy('subject_name')
                    ->values();

                return [
                    'grade_level' => $grade,
                    'subjects' => $subjects,
                    'total_courses' => $byGrade->count(),
                ];
            })
            ->sortBy(fn (array $g): int => $g['grade_level'] ?? 999)
            ->values();

        return view('reports.index', [
            'activeYear' => $activeYear,
            'courses' => $courses,
            'reportGradeGroups' => $reportGradeGroups,
            'isLeadership' => $isLeadership,
        ]);
    }

    public function exportCourses(Request $request): StreamedResponse
    {
        $user = $request->user();
        $selectedYearId = AcademicYear::selectedFor($user)?->id;
        $realActiveYearId = AcademicYear::active()?->id;

        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $realActiveYearId)
            || $user->hasStructuralRole('waka_kurikulum', $realActiveYearId);

        abort_unless($isLeadership || $user->isGuru(), 403);

        $courses = $this->scopedCoursesQuery($user, $isLeadership, $selectedYearId)->get();
        $filename = 'laporan-pembelajaran-lms-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($courses): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for clean Excel opening
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Kelas Digital',
                'Rombel',
                'Mata Pelajaran',
                'Pengajar',
                'Status',
                'Jumlah Siswa',
                'Materi',
                'Tugas',
                'Kuis',
                'Sesi Presensi',
                'Ujian CBTPGRI',
            ]);

            foreach ($courses as $course) {
                fputcsv($handle, [
                    $course->title,
                    $course->classroom?->name ?? '-',
                    $course->subject?->name ?? '-',
                    $course->teachers->pluck('name')->join(', ') ?: '-',
                    $course->status,
                    $course->students_count,
                    $course->materials_count,
                    $course->assignments_count,
                    $course->quizzes_count,
                    $course->learning_sessions_count,
                    $course->cbt_exams_count,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportJournals(Request $request): StreamedResponse
    {
        $user = $request->user();
        $selectedYearId = AcademicYear::selectedFor($user)?->id;
        $realActiveYearId = AcademicYear::active()?->id;

        $isLeadership = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $realActiveYearId)
            || $user->hasStructuralRole('waka_kurikulum', $realActiveYearId);

        abort_unless($isLeadership || $user->isGuru(), 403);

        $courseIds = $this->scopedCoursesQuery($user, $isLeadership, $selectedYearId)->pluck('id');

        $sessions = LearningSession::query()
            ->whereIn('course_id', $courseIds)
            ->with(['course.classroom', 'course.subject', 'teacher', 'learningObjective'])
            ->withCount('attendances')
            ->orderByDesc('session_date')
            ->get();

        $filename = 'jurnal-mengajar-lms-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sessions): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Tanggal',
                'Kelas Digital',
                'Rombel',
                'Mata Pelajaran',
                'Guru',
                'Kode TP',
                'Judul Pertemuan',
                'Topik',
                'Ringkasan Jurnal',
                'Refleksi / Kendala',
                'Tujuan Tercapai',
                'Siswa Hadir Digital',
            ]);

            foreach ($sessions as $session) {
                fputcsv($handle, [
                    $session->session_date?->format('Y-m-d') ?? '-',
                    $session->course?->title ?? '-',
                    $session->course?->classroom?->name ?? '-',
                    $session->course?->subject?->name ?? '-',
                    $session->teacher?->name ?? '-',
                    $session->learningObjective?->code ?? '-',
                    $session->title,
                    $session->topic ?? '-',
                    $session->journal_summary ?? '-',
                    $session->reflection ?? '-',
                    $session->is_achieved ? 'Tercapai' : 'Belum Tercapai',
                    $session->attendances_count,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Builder<Course>
     */
    private function scopedCoursesQuery(User $user, bool $isLeadership, ?int $academicYearId = null)
    {
        return Course::query()
            ->forAcademicYear($academicYearId)
            ->when(! $isLeadership, fn ($q) => $q->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id)))
            ->with(['classroom', 'subject', 'teachers'])
            ->withCount([
                'students',
                'materials',
                'assignments',
                'quizzes',
                'learningSessions',
                'cbtExams',
            ])
            ->orderBy('title');
    }
}
