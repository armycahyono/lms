@extends('layouts.app')

@section('title', $material->title)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('chat.index', ['course_id' => $course->id, 'ref_type' => 'material', 'ref_id' => $material->id]) }}" class="btn btn-sm btn-outline-primary">
            <i class="ti ti-message-question me-1"></i> Tanyakan di Chat
        </a>
        @if(auth()->user()->isSiswa())
            <form action="{{ route('courses.materials.complete', [$course, $material]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm {{ $isCompleted ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="ti ti-check me-1"></i> {{ $isCompleted ? 'Selesai Dibaca' : 'Tandai Selesai' }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge bg-blue-lt">{{ $material->module?->title ?? 'Materi Pembelajaran' }}</span>
            <span class="badge bg-secondary-lt text-uppercase">{{ $material->type }}</span>
        </div>
        <h1 class="h2 mb-4">{{ $material->title }}</h1>

        @if($material->content)
            <div class="mb-4" style="max-width: 72ch; line-height: 1.75; font-size: 1rem; color: #1e293b;">
                {!! nl2br(e($material->content)) !!}
            </div>
        @endif

        @if($material->external_url || $material->file_path)
            <div class="bg-light rounded p-3 d-flex flex-wrap gap-2 align-items-center">
                @if($material->external_url)
                    <a href="{{ $material->external_url }}" target="_blank" rel="noopener" class="btn btn-outline-danger">
                        <i class="ti ti-brand-youtube me-1"></i> Buka Video Pembelajaran
                    </a>
                @endif

                @if($material->file_path)
                    <a href="{{ route('courses.materials.download', [$course, $material]) }}" class="btn btn-primary">
                        <i class="ti ti-download me-1"></i> Unduh File Materi
                    </a>
                @endif
            </div>
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
            <div class="border rounded p-3 mb-2 bg-light-subtle">
                <div class="d-flex justify-content-between">
                    <strong>{{ $discussion->user?->name }}</strong>
                    <span class="text-muted small">{{ $discussion->created_at?->diffForHumans() }}</span>
                </div>
                <p class="mb-0 mt-1">{{ $discussion->body }}</p>
            </div>
        @empty
            <p class="text-muted small text-center py-3 mb-0">Belum ada komentar pada materi ini.</p>
        @endforelse
    </div>
</div>
@endsection
