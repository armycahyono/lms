@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Pusat Informasi</div>
            <h2 class="page-title">Notifikasi</h2>
        </div>
        @if($notifications->contains(fn ($n) => $n->read_at === null))
            <div class="col-auto">
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-checks me-1"></i> Tandai Semua Dibaca
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

@php
    $grouped = $notifications->getCollection()->groupBy(function ($item): string {
        if ($item->created_at?->isToday()) {
            return 'Hari Ini';
        }
        if ($item->created_at?->isYesterday()) {
            return 'Kemarin';
        }

        return 'Sebelumnya';
    });
@endphp

@forelse($grouped as $groupLabel => $items)
    <div class="mb-4">
        <div class="text-muted small fw-bold text-uppercase mb-2 px-1" style="letter-spacing: 0.04em;">
            {{ $groupLabel }}
        </div>

        <div class="d-flex flex-column gap-2">
            @foreach($items as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $type = strtolower((string) ($notification->type ?? 'info'));
                    $iconMeta = match (true) {
                        str_contains($type, 'assignment') || str_contains($type, 'tugas') => ['icon' => 'ti-clipboard-check', 'bg' => 'bg-warning-lt text-warning'],
                        str_contains($type, 'quiz') || str_contains($type, 'kuis') => ['icon' => 'ti-file-check', 'bg' => 'bg-blue-lt text-primary'],
                        str_contains($type, 'material') || str_contains($type, 'materi') => ['icon' => 'ti-book', 'bg' => 'bg-success-lt text-success'],
                        default => ['icon' => 'ti-bell-ringing', 'bg' => 'bg-purple-lt text-purple'],
                    };
                @endphp
                <div class="card {{ $isUnread ? 'border-primary' : '' }}" style="{{ $isUnread ? 'background: #f8fbff;' : '' }}">
                    <div class="card-body p-3 d-flex align-items-start gap-3">
                        <span class="avatar rounded-circle flex-shrink-0 {{ $iconMeta['bg'] }}">
                            <i class="ti {{ $iconMeta['icon'] }} fs-3"></i>
                        </span>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if($isUnread)
                                    <span class="badge bg-primary rounded-circle p-1" title="Belum dibaca" style="width: 8px; height: 8px;"></span>
                                @endif
                                <span class="fw-semibold text-dark">{{ $notification->title }}</span>
                            </div>
                            <p class="text-muted small mb-1">{{ $notification->message }}</p>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                <i class="ti ti-clock me-1"></i>{{ $notification->created_at?->diffForHumans() }}
                            </div>
                        </div>

                        @if($isUnread)
                            <form action="{{ route('notifications.read', $notification) }}" method="POST" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-check me-1"></i> Dibaca
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="ti ti-bell-off fs-1 d-block mb-2 opacity-50"></i>
            Belum ada notifikasi.
        </div>
    </div>
@endforelse

@if($notifications->hasPages())
    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
@endif
@endsection
