@extends('layouts.app')

@section('title', 'Pratinjau Impor Perangkat Ajar')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('teaching-plans.show', $plan) }}" class="text-decoration-none">{{ $plan->title }}</a>
            </div>
            <h2 class="page-title">Pratinjau Impor Template Excel</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('teaching-plans.show', $plan) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Batal &amp; Kembali
            </a>
        </div>
    </div>
</div>

@if(!empty($parsed['errors']))
    <div class="alert alert-danger mb-4">
        <h4 class="alert-title">Ditemukan {{ count($parsed['errors']) }} kesalahan pada file Excel</h4>
        <ul class="mb-0 ps-3 small">
            @foreach($parsed['errors'] as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">CP, Prota &amp; KKTP</div>
            <div class="h3 mb-0 mt-1">{{ $parsed['cp']['title'] ?: $plan->title }}</div>
            <div class="small text-muted">KKTP Min: {{ $parsed['cp']['kktp_min_score'] }} &middot; Target: {{ $parsed['cp']['total_jp'] ?? '-' }} JP</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Tujuan Pembelajaran (ATP) Valid</div>
            <div class="h2 mb-0 mt-1 text-primary">{{ count($parsed['objectives']) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">RPP / Modul Ajar Valid</div>
            <div class="h2 mb-0 mt-1 text-success">{{ count($parsed['lesson_plans']) }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title mb-0">Pratinjau ATP ({{ count($parsed['objectives']) }} baris)</h3></div>
    <div class="table-responsive">
        <table class="table lms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tujuan Pembelajaran</th>
                    <th class="text-center">JP</th>
                    <th>Periode</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parsed['objectives'] as $obj)
                    <tr>
                        <td><span class="badge bg-blue-lt">{{ $obj['code'] }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $obj['title'] }}</div>
                            @if($obj['description'])<div class="small text-muted">{{ $obj['description'] }}</div>@endif
                        </td>
                        <td class="text-center">{{ $obj['jp_allocation'] }} JP</td>
                        <td class="small text-muted">{{ $obj['target_month'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada baris ATP yang terbaca.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title mb-0">Pratinjau RPP / Modul Ajar ({{ count($parsed['lesson_plans']) }} baris)</h3></div>
    <div class="table-responsive">
        <table class="table lms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Judul Modul Ajar</th>
                    <th>TP Terkait</th>
                    <th>Tautan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parsed['lesson_plans'] as $lp)
                    <tr>
                        <td class="fw-semibold">{{ $lp['title'] }}</td>
                        <td><span class="badge bg-secondary-lt">{{ $lp['tp_code'] ?? '-' }}</span></td>
                        <td class="small">
                            @if($lp['external_url'])
                                <a href="{{ $lp['external_url'] }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($lp['external_url'], 50) }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada baris Modul Ajar yang terbaca.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(empty($parsed['errors']))
    <div class="card">
        <div class="card-body">
            <form action="{{ route('teaching-plans.import.confirm', $plan) }}" method="POST" class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                @csrf
                <div class="d-flex flex-wrap gap-4">
                    <label class="form-check mb-0">
                        <input type="radio" name="import_mode" value="append" class="form-check-input" checked>
                        <span class="form-check-label fw-medium">Tambahkan ke ATP &amp; Modul Ajar yang sudah ada</span>
                    </label>
                    <label class="form-check mb-0">
                        <input type="radio" name="import_mode" value="replace" class="form-check-input">
                        <span class="form-check-label fw-medium text-danger">Ganti seluruh ATP &amp; Modul Ajar dengan isi Excel ini</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Konfirmasi &amp; Simpan ke LMS
                </button>
            </form>
        </div>
    </div>
@endif
@endsection
