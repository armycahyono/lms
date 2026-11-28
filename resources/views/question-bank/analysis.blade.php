@extends('layouts.app')

@section('title', 'Analisis Butir Soal Permanen')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('question-bank.index') }}" class="text-decoration-none">Bank Soal</a>
            </div>
            <h2 class="page-title">Analisis &amp; Riwayat Butir Soal Permanen</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('question-bank.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Bank Soal
            </a>
        </div>
    </div>
</div>

@php
    $acc = $question->accuracyPercent();
    $empLabel = $question->empiricalDifficultyLabel();
@endphp

<div class="row row-cards mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Dipakai di Ujian / Kuis</div>
            <div class="h2 mb-0 mt-1 text-primary">{{ $question->usage_count }}x</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Total Dikerjakan Siswa</div>
            <div class="h2 mb-0 mt-1">{{ $question->total_answered }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Tingkat Kebenaran (P)</div>
            <div class="h2 mb-0 mt-1 text-success">{{ $acc !== null ? $acc.'%' : '-' }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Kesukaran Empiris</div>
            <div class="h2 mb-0 mt-1">{{ $empLabel }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title mb-0">Detail Butir Soal</h3>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-2">
            @if($question->subject)<span class="badge bg-blue-lt">{{ $question->subject->name }}</span>@endif
            @if($question->grade_level)<span class="badge bg-secondary-lt">Kelas {{ $question->grade_level }}</span>@endif
            <span class="badge bg-azure-lt">{{ $question->typeLabel() }}</span>
            @if($question->kisi_indicator)<span class="badge bg-indigo-lt">Kisi: {{ $question->kisi_indicator }}</span>@endif
        </div>
        <div class="fs-4 fw-medium katex-render mb-3">{!! $question->question_text !!}</div>

        @if(!empty($question->option_stats_json) && $question->total_answered > 0)
            <h5 class="fw-bold small text-uppercase text-muted mb-2">Distribusi Pilihan Jawaban (Pengecoh A–E)</h5>
            <div class="row g-2">
                @foreach(['A', 'B', 'C', 'D', 'E'] as $opt)
                    @php
                        $count = (int) ($question->option_stats_json[$opt] ?? 0);
                        $pct = round(($count / max(1, $question->total_answered)) * 100, 1);
                        $isKey = str_contains(strtoupper((string) $question->correct_answer), $opt);
                    @endphp
                    @if(isset($question->options_json[$opt]) || $count > 0)
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>
                                    <strong>Opsi {{ $opt }}</strong>
                                    @if($isKey)<span class="badge bg-success-lt ms-1">Kunci</span>@endif
                                </span>
                                <span>{{ $count }} ({{ $pct }}%)</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar {{ $isKey ? 'bg-success' : 'bg-azure' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Riwayat Penggunaan Lintas Ujian &amp; Tahun Ajaran</h3>
    </div>
    <div class="table-responsive">
        <table class="table lms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Sumber</th>
                    <th>Nama Ujian / Kuis</th>
                    <th>Tahun Ajaran</th>
                    <th class="text-center">Dikerjakan</th>
                    <th class="text-center">Benar</th>
                    <th class="text-center">Indeks Kebenaran</th>
                    <th>Terakhir Diperbarui</th>
                </tr>
            </thead>
            <tbody>
                @forelse($question->itemHistories as $hist)
                    <tr>
                        <td>
                            <span class="badge {{ $hist->source_type === 'cbt_exam' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                {{ $hist->source_type === 'cbt_exam' ? 'CBTPGRI' : 'Kuis LMS' }}
                            </span>
                        </td>
                        <td class="fw-semibold">{{ $hist->source_title }}</td>
                        <td>{{ $hist->academic_year_name ?? '-' }}</td>
                        <td class="text-center">{{ $hist->total_answered }}</td>
                        <td class="text-center">{{ $hist->total_correct }}</td>
                        <td class="text-center">
                            <span class="badge bg-azure-lt">{{ $hist->difficulty_index ?? 0 }}%</span>
                        </td>
                        <td class="small text-muted">{{ $hist->recorded_at?->format('d M Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4 small">Belum ada riwayat pengerjaan siswa pada butir soal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
