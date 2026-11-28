@extends('layouts.app')

@section('title', 'Progres Belajar')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Pelacakan Aktivitas</div>
            <h2 class="page-title">Progres &amp; Riwayat Pembelajaran</h2>
        </div>
    </div>
</div>

@if($mode === 'student')
    <div class="row row-cards mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Materi Diselesaikan</div>
                <div class="h2 mb-0 mt-1 text-primary">{{ $summary['materials_done'] }} / {{ $summary['materials_total'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Tugas Dikumpulkan</div>
                <div class="h2 mb-0 mt-1 text-success">{{ $summary['assignments_done'] }} / {{ $summary['assignments_total'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Kuis Diselesaikan</div>
                <div class="h2 mb-0 mt-1">{{ $summary['quizzes_done'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-sm h-100"><div class="card-body">
                <div class="text-muted small">Presensi Sesi</div>
                <div class="h2 mb-0 mt-1 text-warning">{{ $summary['attendance_done'] }} / {{ $summary['attendance_total'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Progres Per Kelas</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($courseProgressList as $item)
                        <a href="{{ route('courses.show', $item['course']) }}" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">{{ $item['course']->title }}</span>
                                <span class="badge bg-blue-lt">{{ $item['materials_pct'] }}%</span>
                            </div>
                            <div class="progress progress-sm mb-2">
                                <div class="progress-bar bg-primary" style="width: {{ $item['materials_pct'] }}%"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                <span>Materi: {{ $item['materials_done'] }}/{{ $item['materials_total'] }}</span>
                                <span>Tugas: {{ $item['assignments_done'] }}/{{ $item['assignments_total'] }}</span>
                                <span>Presensi: {{ $item['attendance_done'] }}/{{ $item['attendance_total'] }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted text-center py-4 small">Belum ada kelas aktif yang diikuti.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Riwayat Aktivitas Terbaru</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($recentActivities as $activity)
                        <div class="list-group-item">
                            <div class="small fw-medium">{{ str_replace('.', ' ', ucfirst($activity->action)) }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $activity->created_at?->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-4 small">Belum ada riwayat aktivitas belajar.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@else
    <div class="vstack gap-4">
        @forelse($gradeGroups as $gradeGrp)
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                        {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Lainnya' }}
                    </span>
                    <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_courses'] }} Rombel</span>
                </div>
                <div class="vstack gap-3">
                    @foreach($gradeGrp['subjects'] as $grp)
                        <div class="card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h3 class="card-title mb-0">{{ $grp['subject_name'] }}</h3>
                                    <span class="badge bg-blue-lt">{{ $grp['courses']->count() }} Rombel</span>
                                </div>
                                <span class="text-muted small"><i class="ti ti-users me-1"></i>{{ $grp['students_count'] }} Siswa</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table lms-table table-vcenter mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kelas</th>
                                            <th>Siswa</th>
                                            <th>Materi</th>
                                            <th>Tugas</th>
                                            <th>Sesi Presensi</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grp['courses'] as $course)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $course->classroom?->name ?? $course->title }}</div>
                                                    <div class="text-muted small">{{ $course->title }}</div>
                                                </td>
                                                <td>{{ $course->students_count }}</td>
                                                <td>{{ $course->materials_count }}</td>
                                                <td>{{ $course->assignments_count }}</td>
                                                <td>{{ $course->learning_sessions_count }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-primary">Detail</a>
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
            <div class="card">
                <div class="card-body text-center text-muted py-5">Belum ada data kelas.</div>
            </div>
        @endforelse
    </div>
@endif
@endsection
