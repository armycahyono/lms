@extends('layouts.app')

@section('title', 'Koreksi Esai per Nomor — '.$quiz->title)

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('courses.quizzes.show', [$course, $quiz]) }}" class="text-decoration-none">{{ $quiz->title }}</a>
            </div>
            <h2 class="page-title">Koreksi per Nomor (Grade-by-Question)</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('courses.quizzes.show', [$course, $quiz]) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Detail Kuis
            </a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-azure-lt">Maksimal Poin: {{ $question->points }}</span>
            @if($question->rubric_keywords || $question->correct_answer)
                <span class="badge bg-warning-lt">
                    Kata Kunci Sorotan: {{ $question->rubric_keywords ?: $question->correct_answer }}
                </span>
            @endif
        </div>
        <div class="fw-semibold fs-4 katex-render">{!! $question->question_text !!}</div>
        @if($question->question_image)
            <div class="mt-2">
                <img src="{{ route('question-bank.media', ['path' => $question->question_image]) }}" alt="Gambar Soal" class="rounded border" style="max-height: 180px;">
            </div>
        @endif
    </div>
</div>

<form action="{{ route('courses.quizzes.questions.grade.store', [$course, $quiz, $question]) }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Jawaban Siswa ({{ $rows->count() }} Peserta)</h3>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Simpan Semua Nilai Nomor Ini
            </button>
        </div>
        <div class="list-group list-group-flush">
            @forelse($rows as $row)
                @php
                    $attempt = $row['attempt'];
                @endphp
                <div class="list-group-item py-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-3">
                            <div class="fw-bold">{{ $attempt->student?->name ?? 'Siswa' }}</div>
                            <div class="text-muted small">
                                Dikirim: {{ $attempt->submitted_at?->format('d M Y H:i') ?? '-' }}
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-secondary-lt">Skor Total Saat Ini: {{ $attempt->score ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="p-3 rounded border bg-light-subtle small" style="line-height: 1.6;">
                                @if(trim($row['raw_answer']) !== '')
                                    {!! $row['highlighted_html'] !!}
                                @else
                                    <span class="text-muted fst-italic">(Tidak menjawab)</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Skor (0–{{ $question->points }})</label>
                            <input type="number"
                                step="0.5"
                                min="0"
                                max="{{ $question->points }}"
                                name="scores[{{ $attempt->id }}]"
                                value="{{ $row['current_score'] }}"
                                class="form-control form-control-sm"
                                placeholder="0">
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">Belum ada siswa yang mengumpulkan jawaban untuk kuis ini.</div>
            @endforelse
        </div>
        @if($rows->isNotEmpty())
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Simpan Semua Nilai Nomor Ini
                </button>
            </div>
        @endif
    </div>
</form>
@endsection
