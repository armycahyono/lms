<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\HomeroomFollowUp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeroomController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $yearId = $activeYear?->id;

        [$isSupervisory, $allowedClassrooms] = $this->resolveAllowedClassrooms($user, $yearId);
        abort_unless($isSupervisory || $allowedClassrooms->isNotEmpty(), 403);

        $classroomId = $request->query('classroom_id');
        $search = trim((string) $request->query('q', ''));
        $allowedClassroomIds = $allowedClassrooms->pluck('id')->all();

        $students = User::query()
            ->where('role', 'siswa')
            ->forAcademicYear($activeYear)
            ->where(function ($q) use ($allowedClassroomIds, $yearId): void {
                $q->whereHas('classrooms', fn ($cq) => $cq->whereIn('classrooms.id', $allowedClassroomIds))
                    ->orWhereHas('studentClassHistories', fn ($hq) => $hq
                        ->whereIn('classroom_id', $allowedClassroomIds)
                        ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)));
            })
            ->when($classroomId, function ($q) use ($classroomId): void {
                $q->where(function ($sub) use ($classroomId): void {
                    $sub->whereHas('classrooms', fn ($cq) => $cq->where('classrooms.id', $classroomId))
                        ->orWhereHas('studentClassHistories', fn ($hq) => $hq->where('classroom_id', $classroomId));
                });
            })
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search): void {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            }))
            ->with([
                'profile',
                'parents.profile',
                'classrooms' => fn ($q) => $q
                    ->when($yearId !== null, fn ($cq) => $cq->where('classrooms.academic_year_id', $yearId)),
                'studentClassHistories' => fn ($q) => $q
                    ->when($yearId !== null, fn ($hq) => $hq->where('academic_year_id', $yearId))
                    ->latest('id')
                    ->with('classroom'),
            ])
            ->withCount([
                'schoolAttendances as late_count' => fn ($q) => $q
                    ->where('status', 'terlambat')
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'schoolAttendances as absent_count' => fn ($q) => $q
                    ->whereIn('status', ['alpa', 'izin', 'sakit'])
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'subjectAttendances as subject_absent_count' => fn ($q) => $q
                    ->whereIn('status', ['a', 'i', 's'])
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'visitations as visitations_count' => fn ($q) => $q
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'incidents as incidents_count' => fn ($q) => $q
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'permits as permits_count' => fn ($q) => $q
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'bkRecords as bk_records_count' => fn ($q) => $q
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
                'homeroomFollowUps as follow_ups_count' => fn ($q) => $q
                    ->when($yearId !== null, fn ($yq) => $yq->where('academic_year_id', $yearId)),
            ])
            ->orderBy('name')
            ->get();

        return view('homeroom.index', [
            'activeYear' => $activeYear,
            'classrooms' => $allowedClassrooms,
            'classroomId' => $classroomId,
            'search' => $search,
            'students' => $students,
        ]);
    }

    public function show(Request $request, User $student): View
    {
        abort_unless($student->isSiswa(), 404);

        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $yearId = $activeYear?->id;

        [$isSupervisory, $allowedClassrooms] = $this->resolveAllowedClassrooms($user, $yearId);
        abort_unless($isSupervisory || $allowedClassrooms->isNotEmpty(), 403);

        if (! $isSupervisory) {
            $allowedIds = $allowedClassrooms->pluck('id')->all();
            $belongs = $student->classrooms()->whereIn('classrooms.id', $allowedIds)->exists()
                || $student->studentClassHistories()->whereIn('classroom_id', $allowedIds)->exists();
            abort_unless($belongs, 403);
        }

        $student->load([
            'profile',
            'parents.profile',
            'classrooms' => fn ($q) => $q
                ->when($yearId !== null, fn ($cq) => $cq->where('classrooms.academic_year_id', $yearId)),
            'studentClassHistories' => fn ($q) => $q
                ->when($yearId !== null, fn ($hq) => $hq->where('academic_year_id', $yearId))
                ->latest('id')
                ->with('classroom'),
        ]);

        $schoolAttendances = $student->schoolAttendances()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('attendance_date')
            ->limit(30)
            ->get();

        $subjectAttendances = $student->subjectAttendances()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->with(['subject:id,name', 'teacher:id,name'])
            ->latest('attendance_date')
            ->limit(30)
            ->get();

        $visitations = $student->visitations()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('planned_date')
            ->get();

        $incidents = $student->incidents()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('incident_date')
            ->get();

        $permits = $student->permits()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('permit_date')
            ->get();

        $bkRecords = $student->bkRecords()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('record_date')
            ->get();

        $followUps = $student->homeroomFollowUps()
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->with('teacher:id,name')
            ->latest('follow_up_date')
            ->latest('id')
            ->get();

        return view('homeroom.show', [
            'activeYear' => $activeYear,
            'student' => $student,
            'schoolAttendances' => $schoolAttendances,
            'subjectAttendances' => $subjectAttendances,
            'visitations' => $visitations,
            'incidents' => $incidents,
            'permits' => $permits,
            'bkRecords' => $bkRecords,
            'followUps' => $followUps,
        ]);
    }

    public function storeFollowUp(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isSiswa(), 404);

        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $yearId = $activeYear?->id;

        [$isSupervisory, $allowedClassrooms] = $this->resolveAllowedClassrooms($user, $yearId);
        abort_unless($isSupervisory || $allowedClassrooms->isNotEmpty(), 403);

        if (! $isSupervisory) {
            $allowedIds = $allowedClassrooms->pluck('id')->all();
            $belongs = $student->classrooms()->whereIn('classrooms.id', $allowedIds)->exists()
                || $student->studentClassHistories()->whereIn('classroom_id', $allowedIds)->exists();
            abort_unless($belongs, 403);
        }

        $validated = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'action_type' => ['required', 'in:komunikasi_ortu,pembinaan,kunjungan_rumah,rujuk_bk,lainnya'],
            'notes' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:terjadwal,selesai'],
        ]);

        HomeroomFollowUp::create([
            'academic_year_id' => $yearId,
            'student_user_id' => $student->id,
            'teacher_id' => $user->id,
            'follow_up_date' => $validated['follow_up_date'],
            'action_type' => $validated['action_type'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Catatan tindak lanjut wali kelas berhasil disimpan.');
    }

    /**
     * @return array{0: bool, 1: Collection<int, Classroom>}
     */
    private function resolveAllowedClassrooms(User $user, ?int $yearId): array
    {
        $isSupervisory = $user->isAdmin()
            || $user->hasStructuralRole('kepsek', $yearId)
            || $user->hasStructuralRole('waka_kurikulum', $yearId);

        if ($isSupervisory) {
            $classrooms = Classroom::query()
                ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
                ->orderBy('name')
                ->get();

            return [true, $classrooms];
        }

        if (! $user->isGuru()) {
            return [false, collect()];
        }

        $classroomIds = $user->assignments()
            ->where('role_type', 'wali_kelas')
            ->when($yearId !== null, fn ($q) => $q->where('academic_year_id', $yearId))
            ->whereNotNull('classroom_id')
            ->pluck('classroom_id')
            ->unique()
            ->all();

        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->orderBy('name')
            ->get();

        return [false, $classrooms];
    }
}
