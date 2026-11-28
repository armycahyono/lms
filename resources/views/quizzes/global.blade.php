@extends('layouts.app')

@section('title', 'Kuis')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Evaluasi Pembelajaran</div>
            <h2 class="page-title">Daftar Kuis & Ujian</h2>
        </div>
    </div>
</div>

<div class="row row-cards">
    @forelse($quizzes as $quiz)
        @php
            $latestAttempt = $mode === 'student' ? $quiz->attempts->first() : null;
            $now = now();
            $isUpcoming = $quiz->start_at && $now->lt($quiz->start_at);
            $isClosed = $quiz->end_at && $now->gt($quiz->end_at);
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="badge bg-blue-lt">
                            {{ $quiz->course?->subject?->name ?? 'Mata Pelajaran' }}
                        </span>
                        @if($mode === 'student')
                            @if($latestAttempt && $latestAttempt->submitted_at)
                                <span class="badge bg-success-lt">Selesai</span>
                            @elseif($isUpcoming)
                                <span class="badge bg-secondary-lt">Akan Datang</span>
                            @elseif($isClosed)
                                <span class="badge bg-danger-lt">Ditutup</span>
                            @else
                                <span class="badge bg-warning-lt">Tersedia</span>
                            @endif
                        @else
                            <span class="badge {{ $quiz->is_published ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                {{ $quiz->is_published ? 'Terbit' : 'Draft' }}
                            </span>
                        @endif
                    </div>

                    <h3 class="card-title mb-1">{{ $quiz->title }}</h3>
                    <div class="text-muted small mb-3">
                        {{ $quiz->course?->title }}
                        @if($quiz->course?->classroom)
                            &middot; {{ $quiz->course->classroom->name }}
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                        <span><i class="ti ti-clock me-1"></i>{{ $quiz->duration_minutes }} menit</span>
                        <span><i class="ti ti-list-numbers me-1"></i>{{ $quiz->questions_count }} soal</span>
                        @if($mode !== 'student')
                            <span><i class="ti ti-users me-1"></i>{{ $quiz->attempts_count }} pengerjaan</span>
                        @endif
                    </div>

                    <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            {{ $quiz->end_at ? 'Tutup: ' . $quiz->end_at->translatedFormat('d M, H:i') : 'Jadwal fleksibel' }}
                        </span>
                        <a href="{{ route('courses.quizzes.show', [$quiz->course_id, $quiz]) }}" class="btn btn-sm btn-outline-primary">
                            {{ $mode === 'student' ? 'Lihat Kuis' : 'Kelola' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="ti ti-file-check fs-1 d-block mb-2 opacity-50"></i>
                    Belum ada kuis yang tersedia.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
