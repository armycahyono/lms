@extends('layouts.app')

@section('title', $material->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small">{{ $material->module?->title ?? 'Materi' }}</div>
            <h2 class="card-title mb-0">{{ $material->title }}</h2>
        </div>
        @if(auth()->user()->isSiswa())
            <form action="{{ route('courses.materials.complete', [$course, $material]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm {{ $isCompleted ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="ti ti-check me-1"></i> {{ $isCompleted ? 'Selesai Dibaca' : 'Tandai Selesai' }}
                </button>
            </form>
        @endif
    </div>
    <div class="card-body">
        @if($material->content)
            <div class="mb-3">{!! nl2br(e($material->content)) !!}</div>
        @endif

        @if($material->external_url)
            <div class="mb-3">
                <a href="{{ $material->external_url }}" target="_blank" rel="noopener" class="btn btn-outline-danger">
                    <i class="ti ti-brand-youtube me-1"></i> Buka Video Pembelajaran
                </a>
            </div>
        @endif

        @if($material->file_path)
            <a href="{{ route('courses.materials.download', [$course, $material]) }}" class="btn btn-primary">
                <i class="ti ti-download me-1"></i> Unduh File Materi
            </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Komentar &amp; Tanya Jawab Materi</h3></div>
    <div class="card-body">
        <form action="{{ route('courses.discussions.store', $course) }}" method="POST" class="mb-4">
            @csrf
            <input type="hidden" name="material_id" value="{{ $material->id }}">
            <textarea name="body" rows="2" class="form-control mb-2" placeholder="Tanyakan sesuatu tentang materi ini..." required></textarea>
            <button type="submit" class="btn btn-sm btn-primary">Kirim Komentar</button>
        </form>

        @forelse($material->discussions as $discussion)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>{{ $discussion->user?->name }}</strong>
                    <span class="text-muted small">{{ $discussion->created_at?->diffForHumans() }}</span>
                </div>
                <p class="mb-0 mt-1">{{ $discussion->body }}</p>
            </div>
        @empty
            <p class="text-muted small mb-0">Belum ada komentar pada materi ini.</p>
        @endforelse
    </div>
</div>
@endsection
