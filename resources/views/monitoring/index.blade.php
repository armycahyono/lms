@extends('layouts.app')

@section('title', 'Monitoring Pembelajaran')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Learning Analytics' }}
            </div>
            <h2 class="page-title">Monitoring Pembelajaran</h2>
        </div>
    </div>
</div>

{{-- KPI Summary --}}
<div class="row row-cards mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-sm h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="avatar rounded-3 bg-success-lt text-success"><i class="ti ti-books fs-2"></i></span>
            <div>
                <div class="text-muted small">Kelas Digital</div>
                <div class="h2 mb-0">{{ $kpis['active_courses'] }} <span class="fs-5 text-muted fw-normal">/ {{ $kpis['draft_courses'] }} draft</span></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="avatar rounded-3 bg-blue-lt text-primary"><i class="ti ti-chalkboard fs-2"></i></span>
            <div>
                <div class="text-muted small">Guru Aktif</div>
                <div class="h2 mb-0">{{ $kpis['teachers'] }}</div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="avatar rounded-3 bg-purple-lt text-purple"><i class="ti ti-school fs-2"></i></span>
            <div>
                <div class="text-muted small">Siswa Aktif</div>
                <div class="h2 mb-0">{{ $kpis['students'] }}</div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="avatar rounded-3 bg-warning-lt text-warning"><i class="ti ti-activity fs-2"></i></span>
            <div>
                <div class="text-muted small">Aktivitas (7 Hari)</div>
                <div class="h2 mb-0">{{ $kpis['recent_activities'] }}</div>
            </div>
        </div></div>
    </div>
</div>

{{-- Segmented Pill Switcher --}}
<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Kategori Monitoring">
        <button type="button" class="lms-pill-tab active js-mon-tab" data-target="mon-teachers" role="tab" aria-selected="true">
            <i class="ti ti-chalkboard"></i>
            <span>Monitoring Guru</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $teachers->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-mon-tab" data-target="mon-courses" role="tab" aria-selected="false">
            <i class="ti ti-books"></i>
            <span>Monitoring Kelas</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $courses->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-mon-tab" data-target="mon-students" role="tab" aria-selected="false">
            <i class="ti ti-school"></i>
            <span>Monitoring Siswa</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $students->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-mon-tab" data-target="mon-logs" role="tab" aria-selected="false">
            <i class="ti ti-activity"></i>
            <span>Aktivitas Pengguna</span>
        </button>
    </div>
</div>

{{-- Panel 1: Monitoring Guru (Modern Row Cards) --}}
<div id="mon-teachers" class="js-mon-panel">
    <div class="d-flex flex-column gap-2">
        @forelse($teachers as $row)
            <div class="lms-row-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="avatar rounded-circle bg-blue-lt text-primary fw-bold flex-shrink-0">
                        {{ strtoupper(substr($row['teacher']->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark text-truncate">{{ $row['teacher']->name }}</div>
                        <div class="text-muted small">{{ $row['teacher']->username }}</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="lms-metric-chip">
                        <i class="ti ti-books text-primary"></i> {{ $row['courses_count'] }} Kelas
                    </span>
                    <span class="lms-metric-chip {{ $row['materials_count'] > 0 ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">
                        <i class="ti ti-book"></i> {{ $row['materials_count'] }} Materi
                    </span>
                    <span class="lms-metric-chip">
                        <i class="ti ti-clipboard-check text-primary"></i> {{ $row['assignments_count'] }} Tugas
                    </span>
                    <span class="lms-metric-chip">
                        <i class="ti ti-file-check text-primary"></i> {{ $row['quizzes_count'] }} Kuis
                    </span>
                    @if($row['ungraded_count'] > 0)
                        <span class="badge bg-warning-lt rounded-pill px-2 py-1">{{ $row['ungraded_count'] }} Belum Dinilai</span>
                    @else
                        <span class="badge bg-success-lt rounded-pill px-2 py-1">Semua Dinilai</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data guru.</div></div>
        @endforelse
    </div>
</div>

{{-- Panel 2: Monitoring Kelas (Tingkat -> Mapel -> Tabel) --}}
<div id="mon-courses" class="js-mon-panel d-none">
    <div class="vstack gap-4">
        @forelse($courseGradeGroups as $gradeGrp)
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                        {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Umum / Lainnya' }}
                    </span>
                    <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_courses'] }} Rombel</span>
                </div>
                <div class="vstack gap-3">
                    @foreach($gradeGrp['subjects'] as $subjGrp)
                        <div class="card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h3 class="card-title mb-0">{{ $subjGrp['subject_name'] }}</h3>
                                    <span class="badge bg-blue-lt">{{ $subjGrp['courses']->count() }} Rombel</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table lms-table table-vcenter mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kelas</th>
                                            <th>Pengajar</th>
                                            <th class="text-center">Siswa</th>
                                            <th class="text-center">Materi</th>
                                            <th class="text-center">Tugas</th>
                                            <th class="text-center">Kuis</th>
                                            <th class="text-center">Sesi</th>
                                            <th>Status</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subjGrp['courses'] as $course)
                                            @php $totalContent = $course->materials_count + $course->assignments_count + $course->quizzes_count; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark">{{ $course->classroom?->name ?? 'Umum' }}</div>
                                                    <div class="text-muted small">{{ $course->title }}</div>
                                                </td>
                                                <td class="small text-muted">{{ $course->teachers->pluck('name')->join(', ') ?: 'Belum ada pengajar' }}</td>
                                                <td class="text-center small">{{ $course->students_count }}</td>
                                                <td class="text-center small">{{ $course->materials_count }}</td>
                                                <td class="text-center small">{{ $course->assignments_count }}</td>
                                                <td class="text-center small">{{ $course->quizzes_count }}</td>
                                                <td class="text-center small">{{ $course->learning_sessions_count }}</td>
                                                <td>
                                                    @if($course->status !== 'active')
                                                        <span class="badge bg-secondary-lt text-uppercase">{{ $course->status }}</span>
                                                    @elseif($totalContent === 0)
                                                        <span class="badge bg-warning-lt">Aktivitas Rendah</span>
                                                    @else
                                                        <span class="badge bg-success-lt">Aktif</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data kelas.</div></div>
        @endforelse
    </div>
</div>

{{-- Panel 3: Monitoring Siswa (Search Bar + Row Cards) --}}
<div id="mon-students" class="js-mon-panel d-none">
    <form method="GET" action="{{ route('monitoring.index') }}" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="tab" value="students">
        <div class="input-icon flex-grow-1" style="max-width: 360px;">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama atau NISN siswa...">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <div class="d-flex flex-column gap-2">
        @forelse($students as $student)
            <div class="lms-row-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="avatar rounded-circle bg-purple-lt text-purple fw-bold flex-shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark text-truncate">{{ $student->name }}</div>
                        <div class="text-muted small">
                            <code>{{ $student->username }}</code>
                            @if($student->classrooms->isNotEmpty())
                                &middot; {{ $student->classrooms->pluck('name')->join(', ') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <span class="lms-metric-chip">
                        <i class="ti ti-books text-primary"></i> {{ $student->enrolled_courses_count }} Kelas
                    </span>
                    <span class="lms-metric-chip bg-success-lt text-success">
                        <i class="ti ti-check"></i> {{ $student->material_progress_count }} Materi Selesai
                    </span>
                    <span class="lms-metric-chip bg-blue-lt text-primary">
                        <i class="ti ti-send"></i> {{ $student->assignment_submissions_count }} Tugas
                    </span>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data siswa.</div></div>
        @endforelse
    </div>
</div>

{{-- Panel 4: Aktivitas Pengguna (Modern Feed) --}}
<div id="mon-logs" class="js-mon-panel d-none">
    <div class="d-flex flex-column gap-2">
        @forelse($recentLogs as $log)
            <div class="lms-row-card d-flex justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-sm rounded-circle bg-blue-lt text-primary">
                        <i class="ti ti-bolt"></i>
                    </span>
                    <div>
                        <div class="small">
                            <strong class="text-dark">{{ $log->user?->name ?? 'Sistem' }}</strong>
                            <span class="badge bg-blue-lt ms-1">{{ $log->action }}</span>
                        </div>
                        <div class="text-muted" style="font-size: 0.72rem;">IP: {{ $log->ip_address ?? '-' }}</div>
                    </div>
                </div>
                <span class="text-muted small flex-shrink-0">{{ $log->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada aktivitas tercatat.</div></div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.js-mon-tab');
    const panels = document.querySelectorAll('.js-mon-panel');

    const activateTab = (targetId) => {
        tabs.forEach((t) => {
            const isActive = t.dataset.target === targetId;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach((p) => p.classList.toggle('d-none', p.id !== targetId));
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.target));
    });

    const urlTab = new URLSearchParams(window.location.search).get('tab');
    if (urlTab === 'students') {
        activateTab('mon-students');
    }
});
</script>
@endpush
