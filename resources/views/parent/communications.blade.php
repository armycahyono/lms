@extends('layouts.app')

@section('title', 'Komunikasi & Pengumuman')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua</div>
            <h2 class="page-title">Komunikasi Sekolah &amp; Wali Kelas</h2>
        </div>
    </div>
</div>

@include('parent.partials.child-switcher')

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Pengumuman Sekolah &amp; Kelas</h3></div>
            <div class="list-group list-group-flush">
                @forelse($announcements as $ann)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="fw-bold">{{ $ann->title }}</div>
                            <span class="badge {{ $ann->course_id ? 'bg-blue-lt' : 'bg-purple-lt' }}">
                                {{ $ann->course?->subject?->name ?? 'Sekolah' }}
                            </span>
                        </div>
                        <div class="text-muted small mb-1">{!! nl2br(e($ann->body)) !!}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Oleh: {{ $ann->user?->name ?? 'Admin' }} &middot; {{ $ann->created_at?->format('d M Y H:i') }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4 small">Belum ada pengumuman.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Notifikasi Presensi &amp; Aktivitas</h3></div>
            <div class="list-group list-group-flush">
                @forelse($notifications as $notif)
                    <div class="list-group-item">
                        <div class="fw-semibold small">{{ $notif->title }}</div>
                        <div class="text-muted small">{{ $notif->body }}</div>
                        <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ $notif->created_at?->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4 small">Belum ada notifikasi.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
