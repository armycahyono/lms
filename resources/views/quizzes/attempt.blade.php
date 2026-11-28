@extends('layouts.app')

@section('title', 'Kerjakan: ' . $quiz->title)

@push('styles')
<style>
    .exam-option-card {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        border: 1px solid var(--lms-border);
        border-radius: 10px;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
        margin-bottom: 0.65rem;
    }

    .exam-option-card:hover {
        background-color: #f8fafc;
        border-color: #94a3b8;
    }

    .exam-option-card:has(input:checked) {
        background-color: var(--lms-primary-lt);
        border-color: var(--lms-primary);
    }

    .exam-nav-btn {
        width: 42px;
        height: 42px;
        padding: 0;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    .exam-nav-btn.is-current {
        outline: 2px solid var(--lms-primary);
        outline-offset: 2px;
    }
</style>
@endpush

@section('content')
@php
    $deadline = $attempt->started_at
        ? $attempt->started_at->copy()->addMinutes((int) $quiz->duration_minutes)
        : now()->addMinutes((int) $quiz->duration_minutes);
    $remainingSeconds = max(0, (int) now()->diffInSeconds($deadline, false));
    $examQuestions = $questions ?? $quiz->questions;
    $totalQuestions = $examQuestions->count();
@endphp

<div class="card mb-3 sticky-top" style="top: 80px; z-index: 1010;">
    <div class="card-body py-2 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="text-muted small">{{ $course->title }}</div>
            <h2 class="h4 mb-0">{{ $quiz->title }}</h2>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="text-muted" style="font-size: 0.7rem;">TERJAWAB</div>
                <div class="fw-bold small" id="exam-answered-summary">0 / {{ $totalQuestions }}</div>
            </div>

            <div class="px-3 py-1 rounded bg-primary-lt text-primary fw-bold d-flex align-items-center gap-2" id="exam-timer-box">
                <i class="ti ti-clock"></i>
                <span id="exam-timer">--:--</span>
            </div>
            <span class="badge bg-success-lt d-none d-md-inline-flex" id="exam-autosave-status">Tersimpan</span>
        </div>
    </div>
</div>

@php
    $savedAnswers = is_array($attempt->answers_json) ? $attempt->answers_json : [];
@endphp

<form id="quiz-attempt-form" action="{{ route('courses.quizzes.attempts.submit', [$course, $quiz, $attempt]) }}" method="POST">
    @csrf
    <input type="hidden" name="focus_loss_count" id="focus-loss-count-input" value="{{ (int) $attempt->focus_loss_count }}">
    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    @forelse($examQuestions as $idx => $question)
                        @php
                            $opts = $shuffledOptionsByQuestion[$question->id] ?? $question->options_json;
                        @endphp
                        <div class="exam-question-slide {{ $idx === 0 ? '' : 'd-none' }}" data-question-index="{{ $idx }}" data-question-id="{{ $question->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <span class="badge bg-blue-lt px-3 py-2">Soal {{ $idx + 1 }} dari {{ $totalQuestions }}</span>
                                <span class="text-muted small fw-medium">{{ $question->points }} poin</span>
                            </div>

                            <div class="fs-3 fw-medium mb-4 katex-render" style="line-height: 1.6;">
                                {!! $question->question_text !!}
                            </div>

                            @if($question->question_image)
                                <div class="mb-4">
                                    <img src="{{ route('question-bank.media', ['path' => $question->question_image]) }}" alt="Gambar Soal {{ $idx + 1 }}" class="img-fluid rounded border" style="max-height: 300px;">
                                </div>
                            @endif

                            @php
                                $savedVal = $savedAnswers[$question->id] ?? $savedAnswers[(string) $question->id] ?? null;
                            @endphp
                            @if($question->type === 'multiple_choice' && is_array($opts))
                                <div>
                                    @foreach($opts as $key => $label)
                                        @php $optImg = $question->option_images_json[$key] ?? null; @endphp
                                        <label class="exam-option-card">
                                            <input class="form-check-input mt-1 exam-answer-input" type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" data-question-index="{{ $idx }}" @checked((string) $savedVal === (string) $key)>
                                            <span class="katex-render">
                                                <strong class="me-1">{{ $key }}.</strong> {!! $label !!}
                                                @if($optImg)
                                                    <div class="mt-2">
                                                        <img src="{{ route('question-bank.media', ['path' => $optImg]) }}" alt="Gambar Opsi {{ $key }}" class="rounded border" style="max-height: 140px;">
                                                    </div>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($question->type === 'multiple_choice_complex' && is_array($opts))
                                @php $savedArr = is_array($savedVal) ? $savedVal : []; @endphp
                                <div>
                                    <div class="text-muted small mb-2">Pilih lebih dari satu jawaban yang benar:</div>
                                    @foreach($opts as $key => $label)
                                        @php $optImg = $question->option_images_json[$key] ?? null; @endphp
                                        <label class="exam-option-card">
                                            <input class="form-check-input mt-1 exam-answer-input" type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $key }}" data-question-index="{{ $idx }}" @checked(in_array((string) $key, array_map('strval', $savedArr), true))>
                                            <span class="katex-render">
                                                <strong class="me-1">{{ $key }}.</strong> {!! $label !!}
                                                @if($optImg)
                                                    <div class="mt-2">
                                                        <img src="{{ route('question-bank.media', ['path' => $optImg]) }}" alt="Gambar Opsi {{ $key }}" class="rounded border" style="max-height: 140px;">
                                                    </div>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($question->type === 'matching' && is_array($opts))
                                @php
                                    $rightChoices = collect($opts)->pluck('right')->filter()->shuffle()->values();
                                    $savedPairs = is_array($savedVal) ? $savedVal : [];
                                @endphp
                                <div class="d-grid gap-2">
                                    @foreach($opts as $pair)
                                        <div class="row g-2 align-items-center p-2 border rounded">
                                            <div class="col-md-6 fw-medium small">{{ $pair['left'] ?? '' }}</div>
                                            <div class="col-md-6">
                                                <select name="answers[{{ $question->id }}][{{ $pair['left'] ?? '' }}]" class="form-select form-select-sm exam-answer-input" data-question-index="{{ $idx }}">
                                                    <option value="">-- Pilih Pasangan --</option>
                                                    @foreach($rightChoices as $rc)
                                                        @php $isPairSelected = ($savedPairs[$pair['left'] ?? ''] ?? '') === $rc; @endphp
                                                        <option value="{{ $rc }}" @selected($isPairSelected)>{{ $rc }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($question->type === 'short_answer')
                                <div>
                                    <label class="form-label small text-muted">Jawaban Singkat</label>
                                    <input type="text" name="answers[{{ $question->id }}]" value="{{ is_string($savedVal) ? $savedVal : '' }}" class="form-control exam-answer-input" data-question-index="{{ $idx }}" placeholder="Tulis jawaban singkat Anda...">
                                </div>
                            @else
                                <div>
                                    <label class="form-label small text-muted">Jawaban Esai</label>
                                    <textarea name="answers[{{ $question->id }}]" rows="5" class="form-control exam-answer-input" data-question-index="{{ $idx }}" placeholder="Tulis jawaban esai Anda di sini...">{{ is_string($savedVal) ? $savedVal : '' }}</textarea>
                                </div>
                            @endif

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-warning btn-sm exam-flag-btn" data-question-index="{{ $idx }}">
                                    <i class="ti ti-flag me-1"></i> Tandai Ragu-ragu
                                </button>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary exam-prev-btn" {{ $idx === 0 ? 'disabled' : '' }}>
                                        <i class="ti ti-chevron-left me-1"></i> Sebelumnya
                                    </button>
                                    @if($idx < $totalQuestions - 1)
                                        <button type="button" class="btn btn-primary exam-next-btn">
                                            Selanjutnya <i class="ti ti-chevron-right ms-1"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmSubmitQuizModal">
                                            <i class="ti ti-send me-1"></i> Kumpulkan Kuis
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">Belum ada soal pada kuis ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Navigasi Nomor Soal</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3" id="exam-question-navigator">
                        @foreach($examQuestions as $idx => $question)
                            <button type="button"
                                class="btn btn-outline-secondary exam-nav-btn {{ $idx === 0 ? 'is-current' : '' }}"
                                data-nav-index="{{ $idx }}">
                                {{ $idx + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="small text-muted d-flex flex-wrap gap-3 mb-4 pt-2 border-top">
                        <span><span class="badge bg-success me-1">&nbsp;</span> Terjawab</span>
                        <span><span class="badge bg-warning me-1">&nbsp;</span> Ragu-ragu</span>
                        <span><span class="badge bg-secondary-lt me-1">&nbsp;</span> Belum</span>
                    </div>

                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#confirmSubmitQuizModal">
                        <i class="ti ti-check me-1"></i> Selesai &amp; Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Pengumpulan --}}
    <div class="modal modal-blur fade" id="confirmSubmitQuizModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-circle text-warning fs-1 mb-2"></i>
                    <h3>Konfirmasi Pengumpulan Kuis</h3>
                    <div class="text-muted small mb-3" id="modal-quiz-summary">
                        Pastikan seluruh jawaban sudah diperiksa sebelum dikirim.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal">Periksa Lagi</button>
                        <button type="submit" class="btn btn-success w-50">Ya, Kirim</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = Array.from(document.querySelectorAll('.exam-question-slide'));
    const navButtons = Array.from(document.querySelectorAll('.exam-nav-btn'));
    const total = slides.length;
    if (total === 0) return;

    let currentIndex = 0;
    const flagged = new Set();

    const isQuestionAnswered = (index) => {
        const slide = slides[index];
        const checks = slide.querySelectorAll('input[type="radio"], input[type="checkbox"]');
        if (checks.length > 0) {
            return Array.from(checks).some((r) => r.checked);
        }
        const selects = slide.querySelectorAll('select');
        if (selects.length > 0) {
            return Array.from(selects).some((s) => s.value.trim().length > 0);
        }
        const textInput = slide.querySelector('input[type="text"], textarea');
        return textInput ? textInput.value.trim().length > 0 : false;
    };

    const refreshStatus = () => {
        let answeredCount = 0;

        navButtons.forEach((btn, idx) => {
            const answered = isQuestionAnswered(idx);
            if (answered) answeredCount++;

            btn.classList.remove('btn-outline-secondary', 'btn-success', 'btn-warning', 'is-current');
            if (flagged.has(idx)) {
                btn.classList.add('btn-warning');
            } else if (answered) {
                btn.classList.add('btn-success');
            } else {
                btn.classList.add('btn-outline-secondary');
            }

            if (idx === currentIndex) {
                btn.classList.add('is-current');
            }
        });

        const summaryEl = document.getElementById('exam-answered-summary');
        if (summaryEl) {
            summaryEl.textContent = `${answeredCount} / ${total}`;
        }

        const modalSummary = document.getElementById('modal-quiz-summary');
        if (modalSummary) {
            const unanswered = total - answeredCount;
            modalSummary.innerHTML = `Terjawab: <strong>${answeredCount}</strong> &middot; Belum dijawab: <strong>${unanswered}</strong> &middot; Ragu-ragu: <strong>${flagged.size}</strong>`;
        }
    };

    const showSlide = (index) => {
        currentIndex = Math.max(0, Math.min(index, total - 1));
        slides.forEach((s, idx) => s.classList.toggle('d-none', idx !== currentIndex));
        refreshStatus();
    };

    navButtons.forEach((btn) => {
        btn.addEventListener('click', () => showSlide(Number(btn.dataset.navIndex)));
    });

    document.querySelectorAll('.exam-prev-btn').forEach((btn) => {
        btn.addEventListener('click', () => showSlide(currentIndex - 1));
    });

    document.querySelectorAll('.exam-next-btn').forEach((btn) => {
        btn.addEventListener('click', () => showSlide(currentIndex + 1));
    });

    document.querySelectorAll('.exam-flag-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const idx = Number(btn.dataset.questionIndex);
            if (flagged.has(idx)) {
                flagged.delete(idx);
            } else {
                flagged.add(idx);
            }
            refreshStatus();
        });
    });

    // Auto-save & Focus-loss tracking
    const form = document.getElementById('quiz-attempt-form');
    const autosaveStatus = document.getElementById('exam-autosave-status');
    const focusInput = document.getElementById('focus-loss-count-input');
    let focusLossCount = Number(focusInput?.value || 0);
    let autosaveTimer = null;

    const runAutosave = () => {
        if (!form) return;
        const fd = new FormData(form);
        fd.set('focus_loss_count', String(focusLossCount));
        if (autosaveStatus) autosaveStatus.textContent = 'Menyimpan...';

        fetch({{ \Illuminate\Support\Js::from(route('courses.quizzes.attempts.autosave', [$course, $quiz, $attempt])) }}, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': {{ \Illuminate\Support\Js::from(csrf_token()) }},
                'Accept': 'application/json',
            },
            body: fd,
        })
            .then((r) => r.json())
            .then((data) => {
                if (data?.saved && autosaveStatus) {
                    autosaveStatus.textContent = 'Tersimpan ' + (data.saved_at || '');
                }
            })
            .catch(() => {
                if (autosaveStatus) autosaveStatus.textContent = 'Menunggu koneksi...';
            });
    };

    const scheduleAutosave = () => {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(runAutosave, 1200);
    };

    document.querySelectorAll('.exam-answer-input').forEach((input) => {
        input.addEventListener('change', () => { refreshStatus(); scheduleAutosave(); });
        input.addEventListener('input', () => { refreshStatus(); scheduleAutosave(); });
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            focusLossCount++;
            if (focusInput) focusInput.value = String(focusLossCount);
            runAutosave();
        }
    });

    setInterval(runAutosave, 15000);
    refreshStatus();

    // Countdown timer & auto-submit
    let remaining = {{ $remainingSeconds }};
    const timerEl = document.getElementById('exam-timer');
    const timerBox = document.getElementById('exam-timer-box');

    const renderTimer = () => {
        const hrs = Math.floor(remaining / 3600);
        const mins = Math.floor((remaining % 3600) / 60);
        const secs = remaining % 60;
        const pad = (n) => String(n).padStart(2, '0');
        timerEl.textContent = hrs > 0 ? `${pad(hrs)}:${pad(mins)}:${pad(secs)}` : `${pad(mins)}:${pad(secs)}`;

        if (remaining <= 300) {
            timerBox.classList.remove('bg-primary-lt', 'text-primary');
            timerBox.classList.add('bg-danger-lt', 'text-danger');
        }
    };

    renderTimer();
    const interval = setInterval(() => {
        if (remaining <= 0) {
            clearInterval(interval);
            form.submit();
            return;
        }
        remaining--;
        renderTimer();
    }, 1000);
});
</script>
@endpush
