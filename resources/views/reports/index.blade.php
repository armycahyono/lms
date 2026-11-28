@extends('layouts.app')

@section('title', 'Laporan Pembelajaran')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Dokumentasi Akademik' }}
            </div>
            <h2 class="page-title">Laporan Pembelajaran</h2>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <a href="{{ route('reports.export.courses') }}" class="btn btn-success">
                <i class="ti ti-file-spreadsheet me-1"></i> Ekspor Kelas (CSV)
            </a>
            <a href="{{ route('reports.export.journals') }}" class="btn btn-outline-success">
                <i class="ti ti-notebook me-1"></i> Ekspor Jurnal Mengajar (CSV)
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-primary">
                <i class="ti ti-printer me-1"></i> Cetak / PDF
            </button>
        </div>
    </div>
</div>

<div class="vstack gap-4">
    @forelse($reportGradeGroups as $gradeGrp)
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
                                        <th class="text-center">Presensi</th>
                                        <th class="text-center">CBT</th>
                                        <th>Status</th>
                                        <th class="text-end d-print-none">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjGrp['courses'] as $course)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $course->classroom?->name ?? 'Umum' }}</div>
                                                <div class="text-muted small">{{ $course->title }}</div>
                                            </td>
                                            <td class="small text-muted">{{ $course->teachers->pluck('name')->join(', ') ?: '-' }}</td>
                                            <td class="text-center small">{{ $course->students_count }}</td>
                                            <td class="text-center small">{{ $course->materials_count }}</td>
                                            <td class="text-center small">{{ $course->assignments_count }}</td>
                                            <td class="text-center small">{{ $course->quizzes_count }}</td>
                                            <td class="text-center small">{{ $course->learning_sessions_count }}</td>
                                            <td class="text-center small">{{ $course->cbt_exams_count }}</td>
                                            <td>
                                                <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : 'bg-secondary-lt' }} text-uppercase">
                                                    {{ $course->status }}
                                                </span>
                                            </td>
                                            <td class="text-end d-print-none">
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
        <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data kelas untuk dilaporkan.</div></div>
    @endforelse
</div>
@endsection
