@extends('layouts.app')

@section('title', 'Buat Ujian Sekolah (CBT)')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('exams.index', ['tab' => 'cbt']) }}" class="text-decoration-none">Kuis &amp; Ujian</a>
            </div>
            <h2 class="page-title">Buat Ujian Sekolah (CBT)</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('exams.index', ['tab' => 'cbt']) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

<form action="{{ route('exams.store') }}" method="POST" id="form-create-cbt-exam">
    @csrf

    {{-- Kartu 1: Informasi & Pengaturan Ujian Sekolah --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">1. Pengaturan Ujian Sekolah &amp; Kelas Tujuan</h3>
        </div>
        <div class="card-body">
            <div class="row g-4">
                {{-- Kiri: Mapel/Tingkat & Detail Ujian --}}
                <div class="col-lg-7 d-grid gap-3">
                    <div>
                        <label class="form-label">Pilih Mapel &amp; Tingkat</label>
                        <select id="cbt-subject-grade-select" class="form-select" required>
                            @foreach($subjectGradeOptions as $opt)
                                <option value="{{ $opt['key'] }}" data-subject-id="{{ $opt['subject_id'] }}" data-grade-level="{{ $opt['grade_level'] ?? '' }}">
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Judul Ujian</label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: PAS Bahasa Indonesia Kelas X" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Jenis Ujian</label>
                            <select name="exam_type" class="form-select" required>
                                <option value="PTS">PTS</option>
                                <option value="PAS">PAS</option>
                                <option value="US">US</option>
                                <option value="Simulasi">Simulasi</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Ref Durasi</label>
                            <div class="input-group">
                                <input type="number" name="duration_minutes" value="90" min="1" class="form-control" required>
                                <span class="input-group-text">menit</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Soal Ujian</label>
                            <input type="number" name="display_question_count" min="1" max="500" class="form-control" placeholder="Semua terpilih">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kode Paket CBT (Opsional)</label>
                            <input type="text" name="cbt_exam_code" class="form-control" placeholder="Otomatis">
                        </div>
                    </div>
                </div>

                {{-- Kanan: Checklist Kelas yang Diampu (Default Tercentang Semua) --}}
                <div class="col-lg-5">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Kelas yang Diampu (Terintegrasi ke CBT)</span>
                        <span class="badge bg-blue-lt" id="cbt-class-count-badge">0 kelas</span>
                    </label>
                    <div id="cbt-class-checklist" class="border rounded p-3 bg-light-subtle d-grid gap-2" style="max-height: 220px; overflow-y: auto;">
                        {{-- Diisi otomatis sesuai Mapel & Tingkat --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu 2: Pilih Soal dari Bank Soal (Model Kartu Seragam) --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h3 class="card-title mb-0">2. Pilih Soal dari Bank Soal</h3>
                <div class="text-muted small">Otomatis tersaring sesuai Mapel &amp; Tingkat yang dipilih di atas.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-cbt-select-all">
                    <i class="ti ti-checks me-1"></i> Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cbt-clear-all">
                    Kosongkan
                </button>
                <span class="badge bg-blue-lt fs-6">
                    Terpilih: <strong id="cbt-selected-count">0</strong> soal
                </span>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Simpan Ujian Sekolah &amp; Pasang Soal
                </button>
            </div>
        </div>

        <div class="card-body border-bottom bg-light-subtle py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" id="filter-q-search" class="form-control form-control-sm" placeholder="Cari teks soal, kisi-kisi, atau bab...">
                </div>
                <div class="col-md-3">
                    <select id="filter-q-type" class="form-select form-select-sm">
                        <option value="">Semua Jenis Soal</option>
                        @foreach(\App\Models\QuestionBank::TYPES as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter-q-difficulty" class="form-select form-select-sm">
                        <option value="">Semua Kesulitan</option>
                        <option value="mudah">Mudah</option>
                        <option value="sedang">Sedang</option>
                        <option value="sulit">Sulit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter-q-visibility" class="form-select form-select-sm">
                        <option value="">Semua (Milik &amp; Dibagikan)</option>
                        <option value="mine">Milik Saya</option>
                        <option value="shared">Dibagikan Rekan</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table lms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 44px;" class="text-center">Pilih</th>
                        <th style="width: 52px;" class="text-center">No.</th>
                        <th>Pertanyaan &amp; Kisi-kisi</th>
                        <th>Mapel &amp; Tingkat</th>
                        <th>Jenis &amp; Kesulitan</th>
                        <th class="text-center">Poin</th>
                    </tr>
                </thead>
                <tbody id="cbt-question-tbody">
                    @forelse($questions as $idx => $q)
                        <tr class="js-cbt-picker-row"
                            style="cursor: pointer;"
                            data-subject-id="{{ $q->subject_id ?? '' }}"
                            data-grade-level="{{ $q->grade_level ?? '' }}"
                            data-type="{{ $q->type }}"
                            data-difficulty="{{ $q->difficulty }}"
                            data-visibility="{{ $q->teacher_id === auth()->id() ? 'mine' : 'shared' }}"
                            data-search="{{ mb_strtolower(strip_tags((string) $q->question_text).' '.$q->kisi_indicator.' '.$q->category) }}">
                            <td class="text-center">
                                <input type="checkbox" name="question_bank_ids[]" value="{{ $q->id }}" class="form-check-input js-cbt-picker-checkbox">
                            </td>
                            <td class="text-center fw-bold text-muted js-row-num">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-semibold small text-dark katex-render">{!! \Illuminate\Support\Str::limit(strip_tags((string) $q->question_text), 140) !!}</div>
                                @if($q->question_image)
                                    <span class="badge bg-cyan-lt mt-1">Gambar Soal</span>
                                @endif
                                @if($q->kisi_indicator)
                                    <div class="text-primary small mt-1" style="font-size: 0.74rem;">Kisi-kisi: {{ $q->kisi_indicator }}</div>
                                @endif
                            </td>
                            <td class="small">
                                @if($q->subject)<span class="badge bg-blue-lt">{{ $q->subject->name }}</span>@endif
                                @if($q->grade_level)<span class="badge bg-secondary-lt">Kelas {{ $q->grade_level }}</span>@endif
                                @if($q->department)<span class="badge bg-purple-lt">{{ $q->department }}</span>@endif
                            </td>
                            <td class="small">
                                <span class="badge bg-azure-lt">{{ $q->typeLabel() }}</span>
                                <span class="badge bg-warning-lt text-uppercase">{{ $q->difficulty }}</span>
                            </td>
                            <td class="text-center fw-bold small">{{ $q->points }}</td>
                        </tr>
                    @empty
                        <tr id="cbt-empty-row">
                            <td colspan="6" class="text-center text-muted py-5">Belum ada soal di Bank Soal untuk mapel/tingkat yang Anda ampu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const courses = {{ \Illuminate\Support\Js::from($coursesPayload) }};
    const subjectGradeSelect = document.getElementById('cbt-subject-grade-select');
    const checklistContainer = document.getElementById('cbt-class-checklist');
    const classCountBadge = document.getElementById('cbt-class-count-badge');

    const rows = Array.from(document.querySelectorAll('.js-cbt-picker-row'));
    const countEl = document.getElementById('cbt-selected-count');

    const searchInput = document.getElementById('filter-q-search');
    const typeSelect = document.getElementById('filter-q-type');
    const diffSelect = document.getElementById('filter-q-difficulty');
    const visSelect = document.getElementById('filter-q-visibility');

    const syncCount = () => {
        let count = 0;
        rows.forEach((row) => {
            const cb = row.querySelector('.js-cbt-picker-checkbox');
            if (cb?.checked) {
                count++;
                row.classList.add('table-primary');
            } else {
                row.classList.remove('table-primary');
            }
        });
        if (countEl) countEl.textContent = String(count);
    };

    const renderClassChecklist = () => {
        const selectedOpt = subjectGradeSelect?.selectedOptions[0];
        const subjId = selectedOpt?.dataset.subjectId || '';
        const gradeLvl = selectedOpt?.dataset.gradeLevel || '';

        const matched = courses.filter((c) => {
            const matchSubj = !subjId || String(c.subject_id) === String(subjId);
            const matchGrade = !gradeLvl || String(c.grade_level || '') === String(gradeLvl);
            return matchSubj && matchGrade;
        });

        checklistContainer.innerHTML = '';
        matched.forEach((c) => {
            const label = document.createElement('label');
            label.className = 'form-check mb-0';
            label.innerHTML = `<input type="checkbox" name="course_ids[]" value="${c.id}" class="form-check-input" checked>
                <span class="form-check-label small fw-medium">${c.classroom_name} <span class="text-muted">(${c.title})</span></span>`;
            checklistContainer.appendChild(label);
        });

        if (classCountBadge) {
            classCountBadge.textContent = `${matched.length} kelas`;
        }
    };

    const filterQuestions = () => {
        const selectedOpt = subjectGradeSelect?.selectedOptions[0];
        const subjId = selectedOpt?.dataset.subjectId || '';
        const gradeLvl = selectedOpt?.dataset.gradeLevel || '';
        const qSearch = (searchInput?.value || '').trim().toLowerCase();
        const qType = typeSelect?.value || '';
        const qDiff = diffSelect?.value || '';
        const qVis = visSelect?.value || '';

        let visibleNum = 0;
        rows.forEach((row) => {
            const rSubj = row.dataset.subjectId || '';
            const rGrade = row.dataset.gradeLevel || '';
            const matchSubj = !subjId || !rSubj || rSubj === subjId;
            const matchGrade = !gradeLvl || !rGrade || rGrade === gradeLvl;
            const matchSearch = !qSearch || (row.dataset.search || '').includes(qSearch);
            const matchType = !qType || row.dataset.type === qType;
            const matchDiff = !qDiff || row.dataset.difficulty === qDiff;
            const matchVis = !qVis || row.dataset.visibility === qVis;

            const show = matchSubj && matchGrade && matchSearch && matchType && matchDiff && matchVis;
            row.classList.toggle('d-none', !show);
            if (show) {
                visibleNum++;
                const numEl = row.querySelector('.js-row-num');
                if (numEl) numEl.textContent = String(visibleNum);
            }
        });
    };

    rows.forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('input, a, button')) return;
            const cb = row.querySelector('.js-cbt-picker-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                syncCount();
            }
        });
        row.querySelector('.js-cbt-picker-checkbox')?.addEventListener('change', syncCount);
    });

    document.getElementById('btn-cbt-select-all')?.addEventListener('click', () => {
        rows.filter((r) => !r.classList.contains('d-none')).forEach((r) => {
            const cb = r.querySelector('.js-cbt-picker-checkbox');
            if (cb) cb.checked = true;
        });
        syncCount();
    });

    document.getElementById('btn-cbt-clear-all')?.addEventListener('click', () => {
        rows.forEach((r) => {
            const cb = r.querySelector('.js-cbt-picker-checkbox');
            if (cb) cb.checked = false;
        });
        syncCount();
    });

    subjectGradeSelect?.addEventListener('change', () => {
        renderClassChecklist();
        filterQuestions();
    });
    searchInput?.addEventListener('input', filterQuestions);
    typeSelect?.addEventListener('change', filterQuestions);
    diffSelect?.addEventListener('change', filterQuestions);
    visSelect?.addEventListener('change', filterQuestions);

    renderClassChecklist();
    filterQuestions();
    syncCount();
});
</script>
@endpush
