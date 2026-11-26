@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name} ({$activeYear->semester})" : 'Portal Pembelajaran Digital' }}
            </div>
            <h2 class="page-title">Selamat datang, {{ auth()->user()->name }}</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('courses.index') }}" class="btn btn-primary">
                <i class="ti ti-books me-1"></i> Lihat Semua Kelas
            </a>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin())
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body"><div class="text-muted small">Guru Aktif</div><div class="h2 mb-0">{{ $stats['teachers'] }}</div></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body"><div class="text-muted small">Siswa Terdaftar</div><div class="h2 mb-0">{{ $stats['students'] }}</div></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body"><div class="text-muted small">Kelas Aktif</div><div class="h2 mb-0 text-success">{{ $stats['active_courses'] }}</div></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body"><div class="text-muted small">Kelas Draft</div><div class="h2 mb-0 text-warning">{{ $stats['draft_courses'] }}</div></div></div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Reset Kata Sandi Siswa ke NISN</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2">
                        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama / NISN...">
                        <button class="btn btn-sm btn-outline-primary" type="submit">Cari</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>NISN</th><th>Nama</th><th>Status Sandi</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td><code>{{ $student->username }}</code></td>
                                    <td>{{ $student->name }}</td>
                                    <td>
                                        @if($student->must_change_password)
                                            <span class="badge bg-warning-lt">Wajib Ganti</span>
                                        @else
                                            <span class="badge bg-success-lt">Sudah Diubah</span>
                                        @endif
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
                                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data siswa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Riwayat Sinkronisasi SIAP</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($recentSyncLogs as $log)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">{{ $log->event_type }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at?->diffForHumans() }}</div>
                            </div>
                            <span class="badge {{ $log->status === 'success' ? 'bg-success-lt' : 'bg-danger-lt' }}">{{ $log->status }}</span>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">Belum ada riwayat sinkronisasi.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@elseif(auth()->user()->isGuru())
    @if($isSupervisory && $schoolStats)
        <div class="alert alert-info mb-4">
            <strong>Mode Monitoring Pimpinan (Kepsek / Waka Kurikulum):</strong>
            Total {{ $schoolStats['total_courses'] }} kelas ({{ $schoolStats['active_courses'] }} aktif, {{ $schoolStats['draft_courses'] }} draft) &middot; {{ $schoolStats['total_students'] }} siswa aktif.
        </div>
    @endif

    @if($ungradedCount > 0)
        <div class="alert alert-warning mb-4">
            Ada <strong>{{ $ungradedCount }}</strong> pengumpulan tugas siswa yang menunggu penilaian Anda.
        </div>
    @endif

    <div class="row row-cards">
        @forelse($teachingCourses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : ($course->status === 'draft' ? 'bg-warning-lt' : 'bg-secondary-lt') }} text-uppercase">
                                {{ $course->status }}
                            </span>
                            <span class="text-muted small">{{ $course->classroom?->name }}</span>
                        </div>
                        <h3 class="card-title mb-1">{{ $course->title }}</h3>
                        <p class="text-muted small mb-3">{{ $course->subject?->name }}</p>
                        <div class="d-flex gap-3 text-muted small mb-3">
                            <span><i class="ti ti-users me-1"></i>{{ $course->students_count }} Siswa</span>
                            <span><i class="ti ti-file-text me-1"></i>{{ $course->materials_count }} Materi</span>
                            <span><i class="ti ti-clipboard-check me-1"></i>{{ $course->assignments_count }} Tugas</span>
                        </div>
                        <a href="{{ route('courses.show', $course) }}" class="btn btn-outline-primary w-100">
                            Kelola Kelas
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center text-muted py-5">Belum ada kelas mengajar yang tersinkron dari SIAP.</div></div>
            </div>
        @endforelse
    </div>
@else
    <div class="row row-cards">
        @forelse($enrolledCourses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="badge bg-blue-lt mb-2">{{ $course->classroom?->name }}</span>
                        <h3 class="card-title mb-1">{{ $course->title }}</h3>
                        <p class="text-muted small mb-3">
                            Pengajar: {{ $course->teachers->pluck('name')->join(', ') ?: '-' }}
                        </p>
                        <div class="d-flex gap-3 text-muted small mb-3">
                            <span><i class="ti ti-book me-1"></i>{{ $course->published_materials_count }} Materi</span>
                            <span><i class="ti ti-checklist me-1"></i>{{ $course->published_assignments_count }} Tugas</span>
                        </div>
                        <a href="{{ route('courses.show', $course) }}" class="btn btn-primary w-100">
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
@endif
@endsection
