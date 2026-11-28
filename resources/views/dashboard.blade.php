@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
@if(auth()->user()->isAdmin())
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle">
                    {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Panel Administrator LMS' }}
                </div>
                <h2 class="page-title">Dashboard Administrator</h2>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Guru Aktif</div>
                <div class="h2 mb-0 mt-1">{{ $stats['teachers'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Siswa Terdaftar</div>
                <div class="h2 mb-0 mt-1">{{ $stats['students'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Kelas Digital</div>
                <div class="h2 mb-0 mt-1 text-success">{{ $stats['active_courses'] }} <span class="fs-5 text-muted fw-normal">/ {{ $stats['draft_courses'] }} draft</span></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Aktivitas (7 Hari)</div>
                <div class="h2 mb-0 mt-1 text-primary">{{ $stats['recent_activities'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="card-title mb-0">Reset Kata Sandi Siswa ke NISN</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama / NISN...">
                        </div>
                        <button class="btn btn-sm btn-primary" type="submit">Cari</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Wali Kelas</th>
                                <th>Nama Orang Tua</th>
                                <th>No. Telp Ortu</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $studentClassroom = $student->classrooms->first()
                                        ?? $student->studentClassHistories->first()?->classroom;
                                    $waliKelasName = $studentClassroom?->userAssignments->first()?->user?->name ?? '-';
                                    $primaryParent = $student->parents->first();
                                    $parentNames = $student->parents->pluck('name')->filter()->implode(', ');
                                    $parentPhones = $student->parents
                                        ->map(fn ($parent) => $parent->profile?->phone)
                                        ->filter()
                                        ->unique()
                                        ->implode(', ');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm rounded-circle bg-purple-lt text-purple fw-bold flex-shrink-0">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $student->name }}</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    <code>{{ $student->username }}</code>
                                                    &middot;
                                                    @if($student->must_change_password)
                                                        <span class="text-warning">Sandi Default</span>
                                                    @else
                                                        <span class="text-success">Aktif</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-medium text-dark">{{ $waliKelasName }}</div>
                                        @if($studentClassroom)
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $studentClassroom->name }}</div>
                                        @endif
                                    </td>
                                    <td class="small text-dark">
                                        {{ $parentNames !== '' ? $parentNames : '-' }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $parentPhones !== '' ? $parentPhones : '-' }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.students.reset-password', $student) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reset kata sandi {{ $student->name }} ke NISN?')">
                                                Reset ke NISN
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4 small">Belum ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <h3 class="card-title mb-0">Pengumuman Sekolah</h3>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-school-announcement">
                        <i class="ti ti-plus me-1"></i> Tambah
                    </button>
                </div>
                <div class="card-body">
                    @if(!empty($schoolAnnouncements) && $schoolAnnouncements->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($schoolAnnouncements as $ann)
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold small">{{ $ann->title }}</div>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($ann->body, 80) }}</div>
                                    </div>
                                    <form action="{{ route('announcements.destroy', $ann) }}" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small text-center py-3">Belum ada pengumuman sekolah.</div>
                    @endif
                </div>
            </div>

            <div class="modal modal-blur fade" id="modal-school-announcement" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('announcements.school.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Terbitkan Pengumuman Sekolah</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body d-grid gap-3">
                                <div>
                                    <label class="form-label">Judul Pengumuman</label>
                                    <input type="text" name="title" class="form-control" placeholder="Judul pengumuman sekolah" required>
                                </div>
                                <div>
                                    <label class="form-label">Isi Pengumuman</label>
                                    <textarea name="body" rows="3" class="form-control" placeholder="Isi pengumuman untuk seluruh guru & siswa..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Terbitkan Pengumuman</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@elseif(auth()->user()->isGuru())
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle">
                    {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Ruang Kerja Guru' }}
                </div>
                <h2 class="page-title">Selamat datang, {{ auth()->user()->name }}</h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('tasks.index', ['filter' => 'ungraded']) }}" class="btn btn-outline-primary">
                    <i class="ti ti-clipboard-check me-1"></i> Periksa Tugas
                </a>
                <a href="{{ route('courses.index') }}" class="btn btn-primary">
                    <i class="ti ti-books me-1"></i> Semua Kelas
                </a>
            </div>
        </div>
    </div>

    @if($isSupervisory && $schoolStats)
        <div class="alert alert-info mb-4">
            <strong>Mode Monitoring Pimpinan (Kepsek / Waka Kurikulum):</strong>
            Total {{ $schoolStats['total_courses'] }} kelas ({{ $schoolStats['active_courses'] }} aktif, {{ $schoolStats['draft_courses'] }} draft) &middot; {{ $schoolStats['total_students'] }} siswa aktif.
        </div>
    @endif

    <div class="row row-cards mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Kelas Diampu</div>
                <div class="h2 mb-0 mt-1">{{ $teacherSummary['courses_count'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Total Siswa</div>
                <div class="h2 mb-0 mt-1">{{ $teacherSummary['students_count'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Materi Diterbitkan</div>
                <div class="h2 mb-0 mt-1 text-success">{{ $teacherSummary['materials_count'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Tugas Belum Dinilai</div>
                <div class="h2 mb-0 mt-1 {{ $ungradedCount > 0 ? 'text-warning' : 'text-success' }}">{{ $ungradedCount }}</div>
            </div></div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-8">
            @if(($pendingAssignments ?? collect())->isNotEmpty() || ($pendingEssayExams ?? collect())->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Perlu Tindakan Segera</h3>
                        <a href="{{ route('tasks.index', ['filter' => 'ungraded']) }}" class="small text-decoration-none">Lihat semua tugas</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($pendingAssignments as $asg)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold">
                                        <span class="badge bg-azure-lt me-1">Tugas</span>
                                        {{ $asg->title }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $asg->course?->subject?->name }} &middot; {{ $asg->course?->classroom?->name }}
                                        &middot; <span class="text-warning fw-semibold">{{ $asg->ungraded_count }} perlu dinilai</span>
                                    </div>
                                </div>
                                <a href="{{ route('courses.assignments.show', [$asg->course_id, $asg]) }}" class="btn btn-sm btn-outline-primary">
                                    Nilai
                                </a>
                            </div>
                        @endforeach
                        @foreach($pendingEssayExams as $cbt)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold">
                                        <span class="badge bg-warning-lt me-1">Koreksi Esai CBT</span>
                                        {{ $cbt->title }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $cbt->course?->subject?->name }} &middot; {{ $cbt->course?->classroom?->name }}
                                        &middot; <span class="text-warning fw-semibold">{{ $cbt->pending_essay_count }} siswa menunggu</span>
                                    </div>
                                </div>
                                <a href="{{ route('exams.show', $cbt) }}" class="btn btn-sm btn-warning">
                                    Koreksi
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Kelompok Ajar (Mapel &amp; Tingkat)</h3>
                <a href="{{ route('courses.index') }}" class="small text-decoration-none">Kelola semua</a>
            </div>
            <div class="row row-cards">
                @forelse($teachingCourseGroups as $grp)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($grp['grade_level'])
                                            <span class="badge bg-indigo-lt">Kelas {{ $grp['grade_level'] }}</span>
                                        @endif
                                        <span class="badge bg-blue-lt">{{ $grp['courses']->count() }} Rombel</span>
                                    </div>
                                    <span class="text-muted small">{{ $grp['students_count'] }} Siswa</span>
                                </div>
                                <h3 class="card-title mb-2">{{ $grp['subject_name'] }}</h3>
                                <div class="d-flex flex-wrap gap-3 text-muted small mb-2">
                                    <span><i class="ti ti-file-text me-1"></i>Maks {{ $grp['max_materials'] }} Materi</span>
                                    <span><i class="ti ti-clipboard-check me-1"></i>Maks {{ $grp['max_assignments'] }} Tugas</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-auto pt-2 border-top">
                                    @foreach($grp['courses'] as $course)
                                        @php
                                            $lags = $grp['courses']->count() > 1 && (
                                                $course->published_materials_count < $grp['max_materials']
                                                || $course->assignments_count < $grp['max_assignments']
                                            );
                                        @endphp
                                        <a href="{{ route('courses.show', $course) }}" class="badge {{ $lags ? 'bg-warning-lt' : 'bg-azure-lt' }} text-decoration-none" title="{{ $lags ? 'Materi/tugas belum lengkap dibanding rombel paralel' : 'Klik untuk kelola kelas' }}">
                                            {{ $course->classroom?->name ?? $course->title }}
                                            @if($lags) <i class="ti ti-alert-triangle ms-1"></i> @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card"><div class="card-body text-center text-muted py-5">Belum ada kelas mengajar yang tersinkron dari SIAP.</div></div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Kuis Berlangsung</h3>
                    <a href="{{ route('quizzes.index') }}" class="small text-decoration-none">Semua</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($activeQuizzes as $quiz)
                        <a href="{{ route('courses.quizzes.show', [$quiz->course_id, $quiz]) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold">{{ $quiz->title }}</div>
                            <div class="text-muted small d-flex justify-content-between mt-1">
                                <span>{{ $quiz->course?->classroom?->name }}</span>
                                <span>{{ $quiz->attempts_count }} pengerjaan</span>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted small py-4 text-center">Tidak ada kuis yang sedang berlangsung.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Aktivitas Siswa Terbaru</h3>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentActivities as $activity)
                        <div class="list-group-item">
                            <div class="small">
                                <strong>{{ $activity->student?->name }}</strong> mengumpulkan
                                <span class="text-primary">{{ $activity->assignment?->title }}</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                {{ $activity->submitted_at?->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small py-4 text-center">Belum ada aktivitas pengumpulan tugas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@else
    {{-- Dashboard Siswa (Playful Interactive) --}}
    <div class="card lms-playful-hero mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <span class="badge bg-white text-success fw-bold mb-2">
                        <i class="ti ti-rocket me-1"></i>
                        {{ $activeYear ? "TA {$activeYear->name}" : 'MISI BELAJAR HARI INI' }}
                    </span>
                    <h2 class="mb-2 text-white">Ayo Lanjut Belajar, {{ auth()->user()->name }}!</h2>
                    <p class="mb-3 text-white-50">
                        Kamu sudah menuntaskan <strong class="text-white">{{ $completedMaterialsCount }} dari {{ $totalPublishedMaterials }}</strong> materi pembelajaran.
                    </p>
                    <div class="progress bg-white bg-opacity-25 mb-2" style="height: 10px; max-width: 420px; border-radius: 999px;">
                        <div class="progress-bar bg-white" style="width: {{ $overallProgress }}%; border-radius: 999px;" role="progressbar" aria-valuenow="{{ $overallProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-white-50">Progres Belajar: <strong class="text-white">{{ $overallProgress }}%</strong></div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @if($nextMaterial)
                        <div class="small text-white-50 mb-1">
                            {{ $lastMaterialProgress ? 'Materi Terakhir Dipelajari' : 'Mulai Misi Pertama' }}
                        </div>
                        <div class="fw-semibold mb-2">{{ $nextMaterial->title }}</div>
                        <a href="{{ route('courses.materials.show', [$nextMaterial->course_id, $nextMaterial]) }}" class="btn btn-light text-primary fw-bold rounded-pill px-4">
                            <i class="ti ti-player-play me-1"></i> Lanjutkan Materi
                        </a>
                    @else
                        <a href="{{ route('courses.index') }}" class="btn btn-light text-primary fw-bold rounded-pill px-4">
                            <i class="ti ti-books me-1"></i> Jelajahi Kelas
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Tugas yang Harus Dikerjakan</h3>
                    <a href="{{ route('tasks.index', ['filter' => 'pending']) }}" class="small text-decoration-none">Lihat semua</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($pendingAssignments as $assignment)
                        @php $isOverdue = $assignment->due_at && $assignment->due_at->isPast(); @endphp
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $assignment->title }}</div>
                                <div class="text-muted small">
                                    {{ $assignment->course?->subject?->name ?? $assignment->course?->title }}
                                    &middot;
                                    <span class="{{ $isOverdue ? 'text-danger fw-medium' : '' }}">
                                        {{ $assignment->due_at ? 'Tenggat: ' . $assignment->due_at->translatedFormat('d M, H:i') : 'Tanpa batas waktu' }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('courses.assignments.show', [$assignment->course_id, $assignment]) }}" class="btn btn-sm btn-primary">
                                Kerjakan
                            </a>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="ti ti-circle-check text-success fs-2 d-block mb-1"></i>
                            Semua tugas sudah dikerjakan!
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Kelas yang Sedang Diikuti</h3>
                <a href="{{ route('courses.index') }}" class="small text-decoration-none">Semua kelas</a>
            </div>
            <div class="row row-cards">
                @forelse($enrolledCourses as $course)
                    @php
                        $doneCount = (int) ($completedCountByCourse[$course->id] ?? 0);
                        $totalCount = (int) $course->published_materials_count;
                        $coursePct = $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0;
                    @endphp
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-blue-lt align-self-start mb-2">{{ $course->classroom?->name }}</span>
                                <h3 class="card-title mb-1">{{ $course->title }}</h3>
                                <p class="text-muted small mb-3">
                                    Pengajar: {{ $course->teachers->pluck('name')->join(', ') ?: '-' }}
                                </p>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progres Materi</span>
                                        <span>{{ $doneCount }}/{{ $totalCount }} ({{ $coursePct }}%)</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: {{ $coursePct }}%"></div>
                                    </div>
                                </div>
                                <a href="{{ route('courses.show', $course) }}" class="btn btn-outline-primary w-100 mt-auto">
                                    Masuk Kelas
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card"><div class="card-body text-center text-muted py-5">Belum ada kelas aktif yang diikuti saat ini.</div></div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Jadwal Kuis</h3>
                    <a href="{{ route('quizzes.index') }}" class="small text-decoration-none">Semua</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($upcomingQuizzes as $quiz)
                        @php $attempt = $quiz->attempts->first(); @endphp
                        <a href="{{ route('courses.quizzes.show', [$quiz->course_id, $quiz]) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="fw-semibold">{{ $quiz->title }}</div>
                                @if($attempt && $attempt->submitted_at)
                                    <span class="badge bg-success-lt">Selesai</span>
                                @else
                                    <span class="badge bg-warning-lt">Tersedia</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">
                                {{ $quiz->course?->subject?->name }} &middot; {{ $quiz->duration_minutes }} menit
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted small py-4 text-center">Belum ada jadwal kuis terdekat.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Pengumuman Sekolah &amp; Guru</h3>
                </div>
                <div class="list-group list-group-flush">
                    @foreach(($schoolAnnouncements ?? collect()) as $ann)
                        <div class="list-group-item bg-blue-lt">
                            <div class="fw-semibold small text-primary"><i class="ti ti-speakerphone me-1"></i>{{ $ann->title }}</div>
                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($ann->body, 100) }}</div>
                        </div>
                    @endforeach
                    @foreach(($classAnnouncements ?? collect()) as $ann)
                        <a href="{{ route('courses.show', $ann->course_id) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold small">{{ $ann->title }} &middot; {{ $ann->course?->subject?->name }}</div>
                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($ann->body, 90) }}</div>
                        </a>
                    @endforeach
                    @forelse($announcements as $announcement)
                        <a href="{{ route('courses.show', $announcement->course_id) }}" class="list-group-item list-group-item-action">
                            <div class="fw-semibold small">{{ $announcement->user?->name }} &middot; {{ $announcement->course?->subject?->name }}</div>
                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($announcement->body, 90) }}</div>
                            <div class="text-muted mt-1" style="font-size: 0.72rem;">{{ $announcement->created_at?->diffForHumans() }}</div>
                        </a>
                    @empty
                        @if(($schoolAnnouncements ?? collect())->isEmpty() && ($classAnnouncements ?? collect())->isEmpty())
                            <div class="list-group-item text-muted small py-4 text-center">Belum ada pengumuman terbaru.</div>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
