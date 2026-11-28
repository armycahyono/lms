@extends('layouts.app')

@section('title', 'Belajar & Nilai Anak')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua</div>
            <h2 class="page-title">Belajar &amp; Nilai Anak</h2>
        </div>
    </div>
</div>

@include('parent.partials.child-switcher')

<div class="mb-3">
    <div class="lms-pill-tabs">
        <a href="{{ route('parent.learning', ['tab' => 'assignments']) }}" class="lms-pill-tab {{ $tab === 'assignments' ? 'active' : '' }}">Tugas Anak</a>
        <a href="{{ route('parent.learning', ['tab' => 'grades']) }}" class="lms-pill-tab {{ $tab === 'grades' ? 'active' : '' }}">Nilai Tugas / Kuis / Ujian</a>
        <a href="{{ route('parent.learning', ['tab' => 'progress']) }}" class="lms-pill-tab {{ $tab === 'progress' ? 'active' : '' }}">Progres Mapel &amp; Kehadiran Digital</a>
    </div>
</div>

@if($tab === 'assignments')
    <div class="lms-table-card">
        <div class="table-responsive">
            <table class="table lms-table align-middle mb-0">
                <thead><tr><th>Tugas</th><th>Mata Pelajaran</th><th>Batas Waktu</th><th>Status</th><th>Nilai</th></tr></thead>
                <tbody>
                    @forelse($assignments as $row)
                        @php $a = $row['assignment']; $sub = $row['submission']; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $a->title }}</td>
                            <td>{{ $a->course?->subject?->name ?? '-' }}</td>
                            <td class="small">{{ $a->due_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                @if($sub)
                                    <span class="badge bg-success-lt">Sudah Dikumpulkan</span>
                                @elseif($a->due_at && $a->due_at->isPast())
                                    <span class="badge bg-danger-lt">Terlambat</span>
                                @else
                                    <span class="badge bg-warning-lt">Belum Dikerjakan</span>
                                @endif
                            </td>
                            <td class="fw-bold">{{ $sub?->score ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada tugas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@elseif($tab === 'grades')
    <div class="lms-table-card">
        <div class="table-responsive">
            <table class="table lms-table align-middle mb-0">
                <thead><tr><th>Penilaian</th><th>Mata Pelajaran</th><th>Jenis</th><th>Nilai</th><th>Tanggal</th></tr></thead>
                <tbody>
                    @forelse($grades as $g)
                        <tr>
                            <td class="fw-semibold">{{ $g['title'] }}</td>
                            <td>{{ $g['subject'] }}</td>
                            <td><span class="badge bg-azure-lt">{{ $g['category'] }}</span></td>
                            <td class="fw-bold text-primary">{{ $g['score'] ?? '-' }}</td>
                            <td class="small text-muted">{{ $g['date']?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada data nilai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="lms-table-card">
        <div class="table-responsive">
            <table class="table lms-table align-middle mb-0">
                <thead><tr><th>Mata Pelajaran</th><th>Kelas</th><th>Materi Selesai</th><th>Progres</th><th>Hadir Digital</th></tr></thead>
                <tbody>
                    @forelse($progressRows as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p['course']->subject?->name ?? $p['course']->title }}</td>
                            <td>{{ $p['course']->classroom?->name ?? '-' }}</td>
                            <td>{{ $p['done_materials'] }} / {{ $p['total_materials'] }}</td>
                            <td style="min-width: 150px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress progress-sm flex-grow-1"><div class="progress-bar bg-success" style="width: {{ $p['percent'] }}%"></div></div>
                                    <span class="small fw-semibold">{{ $p['percent'] }}%</span>
                                </div>
                            </td>
                            <td><span class="badge bg-blue-lt">{{ $p['digital_present'] }} sesi</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada progres belajar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
