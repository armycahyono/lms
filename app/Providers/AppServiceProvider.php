<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\UserAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'profile.show'], function ($view): void {
            $user = Auth::user();

            if (! $user) {
                $view->with([
                    'navUnreadNotificationsCount' => 0,
                    'navPendingTasksCount' => 0,
                    'navIdentityCard' => null,
                    'navCanMonitor' => false,
                    'navAcademicYears' => collect(),
                    'navSelectedYear' => null,
                ]);

                return;
            }

            $selectedYear = AcademicYear::selectedFor($user);
            $selectedYearId = $selectedYear?->id;
            $activeYear = AcademicYear::active();
            $activeYearId = $activeYear?->id;

            $navAcademicYears = ($user->isGuru() || $user->isAdmin()) && Schema::hasTable('academic_years')
                ? AcademicYear::orderByDesc('is_active')->orderByDesc('name')->get()
                : collect();

            $unreadNotifications = $user->lmsNotifications()
                ->whereNull('read_at')
                ->count();

            $pendingTasks = 0;

            if ($user->isSiswa()) {
                $pendingTasks = Assignment::query()
                    ->where('is_published', true)
                    ->whereHas('course', function ($q) use ($user, $selectedYearId): void {
                        $q->where('status', 'active')
                            ->forAcademicYear($selectedYearId)
                            ->whereHas('students', fn ($sq) => $sq->where('users.id', $user->id));
                    })
                    ->whereDoesntHave('submissions', fn ($sq) => $sq->where('student_id', $user->id))
                    ->count();
            } elseif ($user->isGuru()) {
                $pendingTasks = AssignmentSubmission::query()
                    ->whereNull('graded_at')
                    ->whereHas('assignment.course', function ($q) use ($user, $selectedYearId): void {
                        $q->forAcademicYear($selectedYearId)
                            ->whereHas('teachers', fn ($tq) => $tq->where('users.id', $user->id));
                    })
                    ->count();
            }

            $user->loadMissing('profile');
            $profile = $user->profile;

            $classroomName = '-';
            $waliKelasName = '-';

            if ($user->isSiswa()) {
                $classroom = $user->classrooms()
                    ->when($selectedYearId !== null, fn ($q) => $q->where('classrooms.academic_year_id', $selectedYearId))
                    ->first()
                    ?? $user->classrooms()->latest('classrooms.id')->first();

                if ($classroom) {
                    $classroomName = $classroom->name;
                    $waliKelasName = UserAssignment::query()
                        ->where('role_type', 'wali_kelas')
                        ->where('classroom_id', $classroom->id)
                        ->when($selectedYearId !== null, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                        ->with('user')
                        ->first()?->user?->name ?? '-';
                }
            } elseif ($user->isGuru()) {
                $homeroom = $user->assignments()
                    ->where('role_type', 'wali_kelas')
                    ->when($selectedYearId !== null, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                    ->with('classroom')
                    ->first();
                if ($homeroom?->classroom) {
                    $classroomName = $homeroom->classroom->name;
                    $waliKelasName = $user->name;
                }
            }

            $navIdentityCard = [
                'photo' => $profile?->avatar_path,
                'card_bg' => $profile?->card_bg,
                'card_bg_image' => $profile?->card_bg_image,
                'card_settings' => $profile?->card_settings ?? [],
                'name' => $user->name,
                'id_number' => $profile?->nip_nisn ?: $user->username,
                'id_label' => $user->isSiswa() ? 'NISN' : 'NIGS / NIP',
                'status' => ucfirst($user->role),
                'classroom' => $classroomName,
                'wali_kelas' => $waliKelasName,
                'phone' => $profile?->phone ?: '-',
                'email' => $user->email ?: '-',
                'address' => $profile?->address ?: '-',
            ];

            $navCanMonitor = $user->isAdmin()
                || $user->hasStructuralRole('kepsek', $activeYearId)
                || $user->hasStructuralRole('waka_kurikulum', $activeYearId);

            $navCanHomeroomAnalysis = $navCanMonitor
                || $user->hasStructuralRole('wali_kelas', $selectedYearId ?? $activeYearId);

            $view->with([
                'navUnreadNotificationsCount' => $unreadNotifications,
                'navPendingTasksCount' => $pendingTasks,
                'navIdentityCard' => $navIdentityCard,
                'navCanMonitor' => $navCanMonitor,
                'navCanHomeroomAnalysis' => $navCanHomeroomAnalysis,
                'navAcademicYears' => $navAcademicYears,
                'navSelectedYear' => $selectedYear,
                'navActiveYear' => $activeYear,
            ]);
        });
    }
}
