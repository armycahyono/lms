@extends('layouts.app')

@section('title', 'Presensi Sekolah Anak')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua</div>
            <h2 class="page-title">Presensi Sekolah (Salinan Resmi SIAP)</h2>
        </div>
        <div class="col-auto">
            <form method="GET" action="{{ route('parent.attendance') }}" class="d-flex gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm">
                <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
            </form>
        </div>
    </div>
</div>

@include('parent.partials.child-switcher')

<div class="mb-3">
    <div class="lms-pill-tabs">
        <a href="{{ route('parent.attendance', ['tab' => 'summary', 'month' => $month]) }}" class="lms-pill-tab {{ $tab === 'summary' ? 'active' : '' }}">
            Ringkasan &amp; Rekap Bulanan
        </a>
        <a href="{{ route('parent.attendance', ['tab' => 'history', 'month' => $month]) }}" class="lms-pill-tab {{ $tab === 'history' ? 'active' : '' }}">
            Riwayat Harian ({{ $records->count() }})
        </a>
    </div>
</div>

@if($tab === 'summary')
    <div class="row row-cards mb-4">
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Persentase</div><div class="h2 mb-0 text-primary">{{ $attendancePct }}%</div></div></div></div>
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Hadir</div><div class="h2 mb-0 text-success">{{ $summary['hadir'] }}</div></div></div></div>
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Terlambat</div><div class="h2 mb-0 text-warning">{{ $summary['terlambat'] }}</div></div></div></div>
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Izin</div><div class="h2 mb-0 text-azure">{{ $summary['izin'] }}</div></div></div></div>
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Sakit</div><div class="h2 mb-0 text-purple">{{ $summary['sakit'] }}</div></div></div></div>
        <div class="col-md-2 col-6"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Alpa</div><div class="h2 mb-0 text-danger">{{ $summary['alpa'] }}</div></div></div></div>
    </div>
@endif

<div class="lms-table-card">
    <div class="table-responsive">
        <table class="table lms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                    <th>Keterangan SIAP</th>
                    <th>Disinkronkan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->attendance_date->format('d M Y') }}</td>
                        <td><span class="badge bg-blue-lt">{{ $row->statusLabel() }}</span></td>
                        <td>{{ $row->check_in_time ?? '-' }}</td>
                        <td class="small text-muted">{{ $row->notes ?? '-' }}</td>
                        <td class="small text-muted">{{ $row->synced_at?->format('d M Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada data presensi pada bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
