@extends('layouts.app')

@section('title', 'Portal Orang Tua — Rekaman & Pembinaan')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua / Wali Murid</div>
            <h2 class="page-title">Rekaman Visitasi, Kedisiplinan &amp; Pembinaan</h2>
        </div>
    </div>
</div>

@include('parent.partials.child-switcher')

@if(! $activeChild)
    <div class="alert alert-warning">Belum ada data anak yang terhubung dengan akun orang tua Anda.</div>
@else
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Riwayat Visitasi Rumah</h3></div>
                <div class="card-body">
                    @forelse($visitations as $visit)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $visit->visit_type_label ?: ($visit->visit_type ?? 'Visitasi') }}</strong>
                                <span class="badge bg-purple-lt">{{ $visit->status }}</span>
                            </div>
                            <div class="text-muted small">{{ $visit->executed_date?->format('d M Y') ?? $visit->planned_date?->format('d M Y') ?? '-' }}</div>
                            @if($visit->result_summary)<div class="small mt-1">{{ $visit->result_summary }}</div>@endif
                            @if($visit->follow_up_plan)<div class="small text-primary">Rencana: {{ $visit->follow_up_plan }}</div>@endif
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada catatan visitasi.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Catatan Kedisiplinan &amp; Izin Sekolah</h3></div>
                <div class="card-body">
                    @if($incidents->isEmpty() && $permits->isEmpty())
                        <div class="text-muted small">Belum ada catatan insiden atau izin.</div>
                    @else
                        @foreach($incidents as $inc)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-red-lt">{{ $inc->category ?? 'Insiden' }}</span>
                                    <span class="text-muted small">{{ $inc->incident_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="small mt-1">{{ $inc->description }}</div>
                                @if($inc->action_taken)<div class="small text-muted">Tindakan: {{ $inc->action_taken }}</div>@endif
                            </div>
                        @endforeach
                        @foreach($permits as $permit)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-azure-lt">{{ $permit->permit_type_label ?: $permit->permit_type }}</span>
                                    <span class="text-muted small">{{ $permit->permit_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="small mt-1">{{ $permit->reason ?: '-' }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Prestasi &amp; Catatan Perkembangan</h3></div>
                <div class="card-body">
                    @forelse($bkRecords as $rec)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $rec->title }}</strong>
                                <span class="badge bg-green-lt">{{ $rec->typeLabel() }}</span>
                            </div>
                            <div class="text-muted small">{{ $rec->record_date?->format('d M Y') ?? '-' }} @if($rec->category) &middot; {{ $rec->category }} @endif</div>
                            @if($rec->summary)<div class="small mt-1">{{ $rec->summary }}</div>@endif
                            @if($rec->recommendation)<div class="small text-primary">Rekomendasi: {{ $rec->recommendation }}</div>@endif
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada catatan prestasi atau perkembangan.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Tindak Lanjut Wali Kelas</h3></div>
                <div class="card-body">
                    @forelse($followUps as $fu)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-blue-lt">{{ $fu->actionLabel() }}</span>
                                <span class="text-muted small">{{ $fu->follow_up_date?->format('d M Y') }}</span>
                            </div>
                            <div class="small mt-1">{{ $fu->notes }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada catatan tindak lanjut dari wali kelas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
