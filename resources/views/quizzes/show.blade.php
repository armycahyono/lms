@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
</div>

<div class="card mb-4 overflow-hidden" style="border-top: 4px solid var(--lms-primary);">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-blue-lt">{{ $course->title }}</span>
                <span class="badge {{ $quiz->quiz_type === 'remedial' ? 'bg-warning-lt' : ($quiz->quiz_type === 'pengayaan' ? 'bg-success-lt' : 'bg-secondary-lt') }} text-uppercase">
                    {{ $quiz->quiz_type ?? 'reguler' }}
                </span>
                @if($canManage)
                    <span class="badge {{ $quiz->is_published ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                        {{ $quiz->is_published ? 'Terbit' : 'Draft' }}
                    </span>
                @endif
            </div>
            <h2 class="h2 mb-1">{{ $quiz->title }}</h2>
            <div class="d-flex flex-wrap gap-3 text-muted small">
                <span><i class="ti ti-clock me-1"></i>Durasi: {{ $quiz->duration_minutes }} menit</span>
                <span><i class="ti ti-repeat me-1"></i>Maks Percobaan: {{ $quiz->max_attempts }}x</span>
                <span><i class="ti ti-list-numbers me-1"></i>Jumlah Soal: {{ $quiz->questions->count() }}</span>
                <span><i class="ti ti-award me-1"></i>KKM: {{ $quiz->passing_score ?? 75 }}</span>
                @if($canManage)
                    @php $pendingEssayCount = $attempts->where('status', 'submitted')->count(); @endphp
                    @if($pendingEssayCount > 0)
                        <span class="badge bg-warning-lt">{{ $pendingEssayCount }} Menunggu Koreksi Esai</span>
                    @endif
                @endif
            </div>
        </div>
        @if(auth()->user()->isSiswa())
            <form action="{{ route('courses.quizzes.start', [$course, $quiz]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ti ti-player-play me-1"></i> Mulai / Lanjutkan Kuis
                </button>
            </form>
        @elseif($canManage)
            @php
                $hasAttempts = $attempts->isNotEmpty();
                $canDeleteQuiz = ! $hasAttempts && (! $quiz->is_published || auth()->user()->isAdmin());
            @endphp
            <div class="d-flex flex-wrap gap-2">
                @if(! $hasAttempts)
                    <form action="{{ route('courses.quizzes.toggle-publish', [$course, $quiz]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            {{ $quiz->is_published ? 'Jadikan Draft' : 'Publikasikan' }}
                        </button>
                    </form>
                @endif
                @if($canDeleteQuiz)
                    <form action="{{ route('courses.quizzes.destroy', [$course, $quiz]) }}" method="POST" onsubmit="return confirm('Hapus kuis ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Kuis">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

@if($canManage)
    @if($analytics)
        <div class="row row-cards mb-4">
            <div class="col-6 col-lg-3">
                <div class="card card-sm"><div class="card-body">
                    <div class="text-muted small">Peserta Selesai</div>
                    <div class="h2 mb-0 mt-1">{{ $analytics['submitted_count'] }}</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-sm"><div class="card-body">
                    <div class="text-muted small">Rata-rata Nilai</div>
                    <div class="h2 mb-0 mt-1 text-primary">{{ $analytics['avg_score'] }}</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-sm"><div class="card-body">
                    <div class="text-muted small">Lulus KKM (&ge; {{ $quiz->passing_score ?? 75 }})</div>
                    <div class="h2 mb-0 mt-1 text-success">{{ $analytics['passed_count'] }}</div>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-sm"><div class="card-body">
                    <div class="text-muted small">Perlu Remedial</div>
                    <div class="h2 mb-0 mt-1 text-warning">{{ $analytics['remedial_count'] }}</div>
                </div></div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Daftar Soal ({{ $quiz->questions->count() }})</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('courses.quizzes.pick-questions', [$course, $quiz]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-database-import me-1"></i> Pilih Soal dari Bank Soal
                </a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-quiz-question">
                    <i class="ti ti-plus me-1"></i> Tambah Soal
                </button>
            </div>
        </div>
        <div class="list-group list-group-flush">
            @forelse($quiz->questions as $idx => $question)
                <div class="list-group-item">
                    <div class="fw-semibold katex-render">{{ $idx + 1 }}. {!! $question->question_text !!}</div>
                    @if($question->question_image)
                        <div class="mt-1">
                            <img src="{{ route('question-bank.media', ['path' => $question->question_image]) }}" alt="Gambar Soal" class="rounded border" style="max-height: 80px;">
                        </div>
                    @endif
                    <div class="text-muted small">
                        Tipe: {{ $question->type }} &middot; Poin: {{ $question->points }}
                        @if($question->correct_answer) &middot; Kunci: <strong>{{ $question->correct_answer }}</strong> @endif
                    </div>
                    @if(!empty($itemAnalysis) && $itemAnalysis->has($question->id) && $question->correct_answer)
                        @php $stat = $itemAnalysis->get($question->id); @endphp
                        <div class="mt-1 d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.72rem;">
                            <span class="badge bg-azure-lt">Benar: {{ $stat['correct'] }} ({{ $stat['correct_pct'] }}%)</span>
                            <span class="badge {{ $stat['difficulty_label'] === 'Sulit' ? 'bg-danger-lt' : ($stat['difficulty_label'] === 'Sedang' ? 'bg-warning-lt' : 'bg-success-lt') }}">
                                Analisis Butir: {{ $stat['difficulty_label'] }}
                            </span>
                        </div>
                    @endif
                    @if($question->type === 'essay')
                        <div class="mt-2">
                            <a href="{{ route('courses.quizzes.questions.grade', [$course, $quiz, $question]) }}" class="btn btn-sm btn-outline-warning">
                                <i class="ti ti-highlight me-1"></i> Koreksi Esai per Nomor
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="list-group-item text-muted small text-center py-4">Belum ada soal.</div>
            @endforelse
        </div>
    </div>

    @if(!empty($bankQuestions) && $bankQuestions->isNotEmpty())
        <div class="modal modal-blur fade" id="modal-import-bank" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.quizzes.import-bank', [$course, $quiz]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Ambil dari Bank Soal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body" style="max-height: 360px; overflow-y: auto;">
                            @foreach($bankQuestions as $bq)
                                <label class="form-check mb-2">
                                    <input type="checkbox" name="question_bank_ids[]" value="{{ $bq->id }}" class="form-check-input">
                                    <span class="form-check-label small">{{ \Illuminate\Support\Str::limit($bq->question_text, 80) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Import Soal Terpilih</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal modal-blur fade" id="modal-add-quiz-question" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('courses.quizzes.questions.store', [$course, $quiz]) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Soal Kuis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Tipe Soal</label>
                            <select name="type" class="form-select">
                                <option value="multiple_choice">Pilihan Ganda (A-E)</option>
                                <option value="multiple_choice_complex">PG Kompleks (Multi Jawaban)</option>
                                <option value="matching">Menjodohkan</option>
                                <option value="short_answer">Isian Singkat</option>
                                <option value="essay">Esai</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Pertanyaan</label>
                            <textarea name="question_text" rows="2" class="form-control" required></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><input type="text" name="option_a" class="form-control" placeholder="Opsi A"></div>
                            <div class="col-6"><input type="text" name="option_b" class="form-control" placeholder="Opsi B"></div>
                            <div class="col-6"><input type="text" name="option_c" class="form-control" placeholder="Opsi C"></div>
                            <div class="col-6"><input type="text" name="option_d" class="form-control" placeholder="Opsi D"></div>
                            <div class="col-12"><input type="text" name="option_e" class="form-control" placeholder="Opsi E (opsional)"></div>
                        </div>
                        <div>
                            <label class="form-label">Pasangan Menjodohkan (Khusus Menjodohkan: Kiri=Kanan|...)</label>
                            <input type="text" name="matching_pairs" class="form-control" placeholder="Krisis=Masalah|Koda=Penutup">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Kunci Jawaban</label>
                                <input type="text" name="correct_answer" class="form-control" placeholder="A..E / A,C / sinonim1|sinonim2">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Poin</label>
                                <input type="number" step="0.5" name="points" value="10" class="form-control" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Kata Kunci Rubrik Esai (Opsional, pisah koma)</label>
                            <input type="text" name="rubric_keywords" class="form-control" placeholder="Contoh: torsi, presisi, keselamatan kerja">
                        </div>
                        <div>
                            <label class="form-check form-switch mb-0">
                                <input type="hidden" name="save_to_bank" value="0">
                                <input type="checkbox" name="save_to_bank" value="1" class="form-check-input" checked>
                                <span class="form-check-label">Simpan juga ke Bank Soal (dapat dipakai ulang di kelas lain &amp; CBT)</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Riwayat Pengerjaan</h3></div>
    <div class="card-body d-flex flex-column gap-2">
        @forelse($attempts as $attempt)
            @php $studentName = $attempt->student?->name ?? auth()->user()->name; @endphp
            <div class="lms-row-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-sm rounded-circle bg-blue-lt text-primary fw-bold">
                        {{ strtoupper(substr($studentName, 0, 1)) }}
                    </span>
                    <div>
                        <div class="fw-bold text-dark">{{ $studentName }}</div>
                        <div class="text-muted small">
                            <span class="badge bg-blue-lt text-uppercase me-1">{{ $attempt->status }}</span>
                            {{ $attempt->submitted_at?->format('d M Y H:i') ?? 'Sedang berlangsung' }}
                            @if($canManage && $attempt->focus_loss_count > 0)
                                <span class="badge bg-warning-lt ms-1">Pindah Tab: {{ $attempt->focus_loss_count }}x</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    @if($canManage)
                        <form action="{{ route('courses.quizzes.attempts.grade', [$course, $quiz, $attempt]) }}" method="POST" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="number" step="0.01" name="score" value="{{ $attempt->score }}" class="form-control form-control-sm" style="width: 90px;" required>
                            <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                        </form>
                    @else
                        <span class="lms-metric-chip fw-bold text-primary">Nilai: {{ $attempt->score ?? '-' }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4 small">Belum ada percobaan kuis.</div>
        @endforelse
    </div>
</div>
@endsection
