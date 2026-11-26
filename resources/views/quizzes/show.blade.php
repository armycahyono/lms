@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
</div>

<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1">{{ $quiz->title }}</h2>
            <div class="text-muted small">
                Durasi: {{ $quiz->duration_minutes }} menit &middot;
                Maks Percobaan: {{ $quiz->max_attempts }} &middot;
                Jumlah Soal: {{ $quiz->questions->count() }}
            </div>
        </div>
        @if(auth()->user()->isSiswa())
            <form action="{{ route('courses.quizzes.start', [$course, $quiz]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Mulai / Lanjutkan Kuis</button>
            </form>
        @endif
    </div>
</div>

@if($canManage)
    <div class="row row-cards mb-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Tambah Soal Kuis</h3></div>
                <div class="card-body">
                    <form action="{{ route('courses.quizzes.questions.store', [$course, $quiz]) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Tipe Soal</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="multiple_choice">Pilihan Ganda</option>
                                <option value="essay">Esai</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Pertanyaan</label>
                            <textarea name="question_text" rows="2" class="form-control form-control-sm" required></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="text" name="option_a" class="form-control form-control-sm" placeholder="Opsi A"></div>
                            <div class="col-6"><input type="text" name="option_b" class="form-control form-control-sm" placeholder="Opsi B"></div>
                            <div class="col-6"><input type="text" name="option_c" class="form-control form-control-sm" placeholder="Opsi C"></div>
                            <div class="col-6"><input type="text" name="option_d" class="form-control form-control-sm" placeholder="Opsi D"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><input type="text" name="correct_answer" class="form-control form-control-sm" placeholder="Kunci (A/B/C/D)"></div>
                            <div class="col-6"><input type="number" step="0.5" name="points" value="10" class="form-control form-control-sm" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan Soal</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Daftar Soal ({{ $quiz->questions->count() }})</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($quiz->questions as $idx => $question)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $idx + 1 }}. {{ $question->question_text }}</div>
                            <div class="text-muted small">
                                Tipe: {{ $question->type }} &middot; Poin: {{ $question->points }}
                                @if($question->correct_answer) &middot; Kunci: <strong>{{ $question->correct_answer }}</strong> @endif
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">Belum ada soal.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Riwayat Pengerjaan</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Siswa</th><th>Status</th><th>Skor</th><th>Waktu</th></tr></thead>
            <tbody>
                @forelse($attempts as $attempt)
                    <tr>
                        <td>{{ $attempt->student?->name ?? auth()->user()->name }}</td>
                        <td><span class="badge bg-blue-lt text-uppercase">{{ $attempt->status }}</span></td>
                        <td>
                            @if($canManage)
                                <form action="{{ route('courses.quizzes.attempts.grade', [$course, $quiz, $attempt]) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    <input type="number" step="0.01" name="score" value="{{ $attempt->score }}" class="form-control form-control-sm" style="width: 90px;" required>
                                    <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                                </form>
                            @else
                                {{ $attempt->score ?? '-' }}
                            @endif
                        </td>
                        <td class="text-muted small">{{ $attempt->submitted_at?->format('d M Y H:i') ?? 'Sedang berlangsung' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada percobaan kuis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
