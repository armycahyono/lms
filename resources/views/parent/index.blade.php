@extends('layouts.app')

@section('title', 'Portal Orang Tua — Beranda')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua / Wali Murid</div>
            <h2 class="page-title">Beranda Pemantauan Anak</h2>
        </div>
        @if($activeChild)
            <div class="col-auto">
                <form action="{{ route('parent.attendance.resync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-refresh me-1"></i> Sinkron Presensi SIAP
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

@include('parent.partials.child-switcher')

@if(! $activeChild)
    <div class="alert alert-warning">Belum ada data anak yang terhubung dengan akun orang tua Anda. Hubungi admin sekolah/SIAP untuk penautan data.</div>
@else
    <div class="row row-cards">
        {{-- Blok A: Kehadiran di Sekolah (SIAP) --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="ti ti-building-community me-1 text-primary"></i> A. Kehadiran di Sekolah (SIAP)</h3>
                    <a href="{{ route('parent.attendance') }}" class="btn btn-sm btn-outline-primary">Detail</a>
                </div>
                <div class="card-body">
                    <div class="p-3 rounded bg-light-subtle border mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Status Hari Ini ({{ now()->format('d M Y') }})</div>
                            @if($todayAttendance)
                                <div class="h3 mb-0 mt-1">{{ $todayAttendance->statusLabel() }}</div>
                                @if($todayAttendance->check_in_time)
                                    <div class="small text-muted">Jam Masuk: {{ $todayAttendance->check_in_time }}</div>
                                @endif
                            @else
                                <div class="text-muted fw-semibold mt-1">Belum ada catatan hari ini</div>
                            @endif
                        </div>
                        <span class="badge {{ $todayAttendance?->status === 'hadir' ? 'bg-success-lt' : ($todayAttendance ? 'bg-warning-lt' : 'bg-secondary-lt') }} fs-6">
                            {{ $todayAttendance?->statusLabel() ?? '-' }}
                        </span>
                    </div>

                    <div class="row g-2 text-center mb-3">
                        <div class="col"><div class="p-2 rounded bg-success-lt"><div class="fw-bold">{{ $monthSummary['hadir'] }}</div><div class="small">Hadir</div></div></div>
                        <div class="col"><div class="p-2 rounded bg-warning-lt"><div class="fw-bold">{{ $monthSummary['terlambat'] }}</div><div class="small">Telat</div></div></div>
                        <div class="col"><div class="p-2 rounded bg-azure-lt"><div class="fw-bold">{{ $monthSummary['izin'] }}</div><div class="small">Izin</div></div></div>
                        <div class="col"><div class="p-2 rounded bg-purple-lt"><div class="fw-bold">{{ $monthSummary['sakit'] }}</div><div class="small">Sakit</div></div></div>
                        <div class="col"><div class="p-2 rounded bg-danger-lt"><div class="fw-bold">{{ $monthSummary['alpa'] }}</div><div class="small">Alpa</div></div></div>
                    </div>

                    <div class="small fw-semibold text-muted mb-2">Riwayat Singkat</div>
                    <div class="list-group list-group-flush">
                        @forelse($recentAttendances as $att)
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center small">
                                <span>{{ $att->attendance_date->format('d M Y') }} {{ $att->notes ? '— '.$att->notes : '' }}</span>
                                <span class="badge bg-blue-lt">{{ $att->statusLabel() }}</span>
                            </div>
                        @empty
                            <div class="text-muted small py-2">Belum ada riwayat presensi dari SIAP.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Blok B: Aktivitas Pembelajaran LMS --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="ti ti-books me-1 text-success"></i> B. Aktivitas Pembelajaran LMS</h3>
                    <a href="{{ route('parent.learning') }}" class="btn btn-sm btn-outline-success">Detail</a>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light-subtle">
                                <div class="text-muted small">Tugas Belum Dikerjakan</div>
                                <div class="h2 mb-0 text-primary">{{ $pendingAssignments->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light-subtle">
                                <div class="text-muted small">Tugas Terlambat</div>
                                <div class="h2 mb-0 {{ $overdueCount > 0 ? 'text-danger' : 'text-success' }}">{{ $overdueCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="small fw-semibold text-muted mb-2">Nilai Terbaru</div>
                    <div class="list-group list-group-flush mb-3">
                        @forelse($recentGrades as $g)
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center small">
                                <div>
                                    <div class="fw-semibold">{{ $g['title'] }}</div>
                                    <div class="text-muted">{{ $g['subject'] }} &middot; {{ $g['type'] }}</div>
                                </div>
                                <span class="badge bg-success-lt fw-bold">{{ $g['score'] }}</span>
                            </div>
                        @empty
                            <div class="text-muted small py-2">Belum ada nilai terbaru.</div>
                        @endforelse
                    </div>

                    <div class="small fw-semibold text-muted mb-2">Progres Materi per Mapel</div>
                    @forelse($courseProgress->take(3) as $cp)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $cp['course']->subject?->name ?? $cp['course']->title }}</span>
                                <span class="fw-semibold">{{ $cp['percent'] }}%</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: {{ $cp['percent'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">Belum terdaftar di kelas digital.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
