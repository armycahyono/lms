@extends('layouts.app')

@section('title', 'Kelola Ujian: ' . $exam->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('exams.index', ['tab' => 'cbt']) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke Kuis &amp; Ujian
    </a>
</div>

<div class="card mb-3 bg-azure-lt border-0">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge {{ $exam->questions->isNotEmpty() ? 'bg-success' : 'bg-primary' }} rounded-pill">1</span>
                <span class="fw-semibold">Pilih Soal dari Bank Soal ({{ $exam->questions->count() }} soal)</span>
                <i class="ti ti-chevron-right text-muted"></i>
                <span class="badge {{ $exam->last_synced_at ? 'bg-success' : 'bg-secondary' }} rounded-pill">2</span>
                <span class="fw-semibold">Kirim Paket ke CBTPGRI</span>
                <i class="ti ti-chevron-right text-muted"></i>
                <span class="badge {{ $resultsByStudent->isNotEmpty() ? 'bg-success' : 'bg-secondary' }} rounded-pill">3</span>
                <span class="fw-semibold">Tarik Nilai CBTPGRI ({{ $resultsByStudent->count() }} siswa)</span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-blue-lt">{{ $exam->exam_type }}</span>
                @if($exam->cbt_exam_code)
                    <span class="badge bg-azure-lt">Kode CBTPGRI: {{ $exam->cbt_exam_code }}</span>
                @endif
                @if($exam->last_synced_at)
                    <span class="badge bg-success-lt">Tersinkron: {{ $exam->last_synced_at->diffForHumans() }}</span>
                @endif
            </div>
            <h2 class="mb-1">{{ $exam->title }}</h2>
            <div class="text-muted small">{{ $exam->course?->title }} &middot; {{ $exam->course?->classroom?->name }}</div>
            @if($exam->start_at)
                <div class="text-muted small mt-1">
                    <i class="ti ti-calendar-event me-1 text-primary"></i>
                    Jadwal CBTPGRI: <strong>{{ $exam->start_at->translatedFormat('d M Y H:i') }}</strong>@if($exam->end_at) &ndash; {{ $exam->end_at->format('H:i') }}@endif WIB
                    @if($exam->session_name || $exam->room_name)
                        &middot; {{ collect([$exam->session_name, $exam->room_name])->filter()->implode(' · ') }}
                    @endif
                </div>
            @endif
            @if(($packageSiblings ?? collect())->count() > 1)
                <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                    <span class="text-muted small me-1">Kelas dalam paket:</span>
                    @foreach($packageSiblings as $sib)
                        <a href="{{ route('exams.show', $sib) }}" class="badge {{ $sib->id === $exam->id ? 'bg-primary text-white' : 'bg-azure-lt' }} text-decoration-none">
                            {{ $sib->course?->classroom?->name ?? $sib->course?->title }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('exams.edit', $exam) }}" class="btn btn-outline-secondary">
                <i class="ti ti-edit me-1"></i> Edit Paket
            </a>
            <a href="{{ route('exams.questions.pick', $exam) }}" class="btn btn-outline-primary">
                <i class="ti ti-database-import me-1"></i> Pilih Soal dari Bank Soal
            </a>
            <form action="{{ route('exams.push-cbt', $exam) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-cloud-upload me-1"></i> Kirim Paket ke CBTPGRI
                </button>
            </form>
            <form action="{{ route('exams.pull-schedule', $exam) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-info">
                    <i class="ti ti-calendar-down me-1"></i> Tarik Jadwal CBTPGRI
                </button>
            </form>
            <form action="{{ route('exams.pull-cbt', $exam) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-success">
                    <i class="ti ti-cloud-download me-1"></i> Tarik Nilai CBTPGRI
                </button>
            </form>
            @php
                $canDeletePackage = $exam->results->isEmpty()
                    && (auth()->user()->isAdmin() || ($exam->last_synced_at === null && $exam->start_at === null));
            @endphp
            @if($canDeletePackage)
                <form action="{{ route('exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Hapus seluruh paket ujian ini? Paket hanya bisa dihapus jika belum dirilis dan belum ada nilai siswa.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" title="Hapus Paket Ujian">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Bagian Ujian CBTPGRI">
        <button type="button" class="lms-pill-tab active js-exam-tab" data-target="exam-questions" role="tab" aria-selected="true">
            <i class="ti ti-list-numbers"></i>
            <span>Paket Soal Ujian</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $exam->questions->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-exam-tab" data-target="exam-results" role="tab" aria-selected="false">
            <i class="ti ti-award"></i>
            <span>Rekap Nilai Siswa</span>
            <span class="badge bg-success-lt rounded-pill">{{ $resultsByStudent->count() }}</span>
            @if(($pendingEssayCount ?? 0) > 0)
                <span class="badge bg-warning text-dark rounded-pill">{{ $pendingEssayCount }} Koreksi Esai</span>
            @endif
        </button>
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane active" id="exam-questions">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Daftar Soal dalam Paket Ujian</h3></div>
            <div class="list-group list-group-flush">
                @forelse($exam->questions as $idx => $q)
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <span class="badge bg-blue-lt">#{{ $idx + 1 }}</span>
                                <span class="badge bg-azure-lt">{{ $q->typeLabel() }}</span>
                                <span class="badge bg-warning-lt text-uppercase">{{ $q->difficulty }}</span>
                            </div>
                            <div class="fw-semibold small katex-render">{!! $q->question_text !!}</div>
                            @if($q->question_image)
                                <div class="mt-1">
                                    <img src="{{ route('question-bank.media', ['path' => $q->question_image]) }}" alt="Gambar Soal" class="rounded border" style="max-height: 80px;">
                                </div>
                            @endif
                            @if(!empty($q->option_images_json))
                                <span class="badge bg-teal-lt mt-1">{{ count($q->option_images_json) }} Gambar Opsi</span>
                            @endif
                            @if($q->kisi_indicator)
                                <div class="text-muted small">Kisi-kisi: {{ $q->kisi_indicator }}</div>
                            @endif
                        </div>
                        <form action="{{ route('exams.questions.detach', [$exam, $q]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Keluarkan dari paket"><i class="ti ti-x"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-5 small">
                        Belum ada soal di paket ujian ini. Klik <strong>Pilih Soal dari Bank Soal</strong> untuk menyusun paket soal sebelum mengirim ke CBTPGRI.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="tab-pane d-none" id="exam-results">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Rekap Nilai &amp; Koreksi Esai Siswa</h3>
                @if(($pendingEssayCount ?? 0) > 0)
                    <span class="badge bg-warning-lt">{{ $pendingEssayCount }} siswa menunggu koreksi esai</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table lms-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>NISN</th>
                            <th>Status</th>
                            <th>Rincian (PG + Esai)</th>
                            <th>Nilai Akhir</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            @php
                                $result = $resultsByStudent->get($student->id);
                                $essayAnswers = $result ? $result->answers->where('question_type', 'essay') : collect();
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $student->name }}</td>
                                <td><code>{{ $student->username }}</code></td>
                                <td>
                                    @if(!$result)
                                        <span class="badge bg-secondary-lt">Belum Ada Nilai</span>
                                    @elseif($result->isPendingEssay())
                                        <span class="badge bg-warning-lt">Menunggu Koreksi Esai</span>
                                    @else
                                        <span class="badge bg-success-lt">Selesai</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @if($result && ($result->auto_score !== null || $result->essay_score !== null))
                                        PG: <strong>{{ $result->auto_score ?? '-' }}</strong>
                                        &middot;
                                        Esai: <strong>{{ $result->essay_score ?? '-' }}</strong>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('exams.results.store', $exam) }}" method="POST" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="number" step="0.01" name="score" value="{{ $result?->score }}" class="form-control form-control-sm" style="width: 100px;" min="0" max="100" required>
                                        <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    @if($result && $essayAnswers->isNotEmpty())
                                        <button type="button" class="btn btn-sm {{ $result->isPendingEssay() ? 'btn-warning' : 'btn-outline-primary' }} js-toggle-essay" data-target="essay-panel-{{ $result->id }}">
                                            <i class="ti ti-pencil me-1"></i> {{ $result->isPendingEssay() ? 'Koreksi Esai' : 'Lihat Koreksi' }} ({{ $essayAnswers->count() }})
                                        </button>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @if($result && $essayAnswers->isNotEmpty())
                                <tr id="essay-panel-{{ $result->id }}" class="d-none bg-light">
                                    <td colspan="6" class="p-3">
                                        <form action="{{ route('exams.results.grade', $result) }}" method="POST">
                                            @csrf
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="fw-semibold">
                                                    Koreksi Jawaban Esai: {{ $student->name }}
                                                    @if($result->auto_score !== null)
                                                        <span class="text-muted fw-normal small ms-2">(Skor PG Otomatis: {{ $result->auto_score }})</span>
                                                    @endif
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-check me-1"></i> Simpan &amp; Finalisasi Nilai
                                                </button>
                                            </div>
                                            <div class="vstack gap-2">
                                                @foreach($essayAnswers as $ans)
                                                    <div class="card card-body py-2 px-3">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                                                            <span class="badge bg-blue-lt">Soal #{{ $ans->question_number }} (Maks: {{ $ans->max_points }})</span>
                                                            @if($ans->question?->question_text)
                                                                <span class="text-muted small">{{ Str::limit(strip_tags($ans->question->question_text), 120) }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="p-2 rounded bg-white border small mb-2" style="white-space: pre-wrap;">{{ $ans->student_answer ?: '(Tidak dijawab)' }}</div>
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col-12 col-sm-3">
                                                                <label class="form-label form-label-sm mb-1">Skor Esai (0 - {{ $ans->max_points }})</label>
                                                                <input type="number" step="0.01" min="0" max="{{ $ans->max_points }}" name="scores[{{ $ans->id }}]" value="{{ old("scores.{$ans->id}", $ans->earned_points) }}" class="form-control form-control-sm" required>
                                                            </div>
                                                            <div class="col-12 col-sm-9">
                                                                <label class="form-label form-label-sm mb-1">Catatan Guru (opsional)</label>
                                                                <input type="text" name="feedback[{{ $ans->id }}]" value="{{ old("feedback.{$ans->id}", $ans->teacher_feedback) }}" class="form-control form-control-sm" placeholder="Catatan koreksi untuk siswa...">
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada siswa di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.js-exam-tab');
    const panes = document.querySelectorAll('.tab-content .tab-pane');
    tabs.forEach((btn) => {
        btn.addEventListener('click', () => {
            tabs.forEach((t) => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panes.forEach((p) => {
                p.classList.remove('active');
                p.classList.add('d-none');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
            const target = document.getElementById(btn.dataset.target);
            if (target) {
                target.classList.add('active');
                target.classList.remove('d-none');
            }
        });
    });

    document.querySelectorAll('.js-toggle-essay').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = document.getElementById(btn.dataset.target);
            if (row) {
                row.classList.toggle('d-none');
            }
        });
    });
});
</script>
@endpush
