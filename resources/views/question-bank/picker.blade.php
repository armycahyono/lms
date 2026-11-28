@extends('layouts.app')

@section('title', 'Pilih Soal dari Bank Soal — '.$targetTitle)

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ $backUrl }}" class="text-decoration-none">{{ $targetSubtitle }}</a>
            </div>
            <h2 class="page-title">Pilih Soal dari Bank Soal: {{ $targetTitle }}</h2>
            <div class="text-muted small mt-1">
                Sudah terpasang: <strong>{{ $existingCount }}</strong> soal &middot; Centang butir soal di bawah ini lalu klik <strong>Tambahkan Soal Terpilih</strong>.
            </div>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('question-bank.index') }}" class="btn btn-outline-info">
                <i class="ti ti-database me-1"></i> Kelola Bank Soal
            </a>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

{{-- Filter Pencarian Lengkap --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari Teks Soal / Kisi-kisi</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Ketik kata kunci...">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Tipe Soal</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    @foreach(\App\Models\QuestionBank::TYPES as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected($filters['type'] === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Tingkat</label>
                <select name="grade_level" class="form-select form-select-sm">
                    <option value="">Semua Tingkat</option>
                    @foreach(($gradeLevels ?? [10, 11, 12]) as $gl)
                        <option value="{{ $gl }}" @selected((string) $filters['grade_level'] === (string) $gl)>Kelas {{ $gl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">Jurusan</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">Kesulitan</label>
                <select name="difficulty" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="mudah" @selected($filters['difficulty'] === 'mudah')>Mudah</option>
                    <option value="sedang" @selected($filters['difficulty'] === 'sedang')>Sedang</option>
                    <option value="sulit" @selected($filters['difficulty'] === 'sulit')>Sulit</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Visibilitas / Bab</label>
                <select name="visibility" class="form-select form-select-sm">
                    <option value="">Semua (Milik &amp; Dibagikan)</option>
                    <option value="private" @selected($filters['visibility'] === 'private')>Privat Saya</option>
                    <option value="shared" @selected($filters['visibility'] === 'shared')>Dibagikan Rekan</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<form id="form-picker-questions" action="{{ $submitUrl }}" method="POST">
    @csrf

    {{-- Sticky Selection Bar --}}
    <div class="card mb-3 sticky-top shadow-sm border-primary" style="top: 74px; z-index: 1020;">
        <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-select-all-page">
                    <i class="ti ti-checks me-1"></i> Pilih Semua di Halaman Ini
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-all-page">
                    Kosongkan Pilihan
                </button>
                <span class="badge bg-blue-lt fs-6">
                    Terpilih: <strong id="picker-selected-count">0</strong> soal
                </span>
                <span class="badge bg-azure-lt fs-6">
                    Total Poin: <strong id="picker-selected-points">0</strong>
                </span>
            </div>
            <button type="submit" class="btn btn-primary" id="btn-submit-picker" disabled>
                <i class="ti ti-plus me-1"></i> Tambahkan Soal Terpilih
            </button>
        </div>
    </div>

    <div class="lms-table-card">
        <div class="table-responsive">
            <table class="table lms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 44px;" class="text-center">Pilih</th>
                        <th style="width: 52px;" class="text-center">No.</th>
                        <th>Pertanyaan, Media &amp; Kisi-kisi</th>
                        <th>Tipe &amp; Kesulitan</th>
                        <th>Tingkat / Jurusan</th>
                        <th class="text-center">Poin &amp; Statistik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                        @php
                            $rowNumber = ($questions->firstItem() ?? 1) + $loop->index;
                        @endphp
                        <tr class="js-picker-row" style="cursor: pointer;">
                            <td class="text-center">
                                <input type="checkbox"
                                    name="question_bank_ids[]"
                                    value="{{ $q->id }}"
                                    data-points="{{ (float) $q->points }}"
                                    class="form-check-input js-picker-checkbox">
                            </td>
                            <td class="text-center fw-bold text-muted">{{ $rowNumber }}</td>
                            <td>
                                <div class="fw-semibold text-dark small katex-render">{!! $q->question_text !!}</div>
                                @if(is_array($q->options_json) && in_array($q->type, ['multiple_choice', 'multiple_choice_complex'], true))
                                    <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                        @foreach($q->options_json as $optKey => $optText)
                                            <span class="me-2"><strong>{{ $optKey }}.</strong> {{ \Illuminate\Support\Str::limit(strip_tags((string) $optText), 28) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($q->question_image)
                                    <div class="mt-1">
                                        <img src="{{ route('question-bank.media', ['path' => $q->question_image]) }}" alt="Gambar Soal" class="rounded border" style="max-height: 70px;">
                                    </div>
                                @endif
                                @if(!empty($q->option_images_json))
                                    <span class="badge bg-teal-lt mt-1">{{ count($q->option_images_json) }} Gambar Opsi</span>
                                @endif
                                @if($q->kisi_indicator)
                                    <div class="text-primary small mt-1" style="font-size: 0.74rem;">Kisi-kisi: {{ $q->kisi_indicator }}</div>
                                @endif
                                @if($q->category)
                                    <div class="text-muted" style="font-size: 0.72rem;">Bab: {{ $q->category }}</div>
                                @endif
                            </td>
                            <td class="small">
                                <span class="badge bg-azure-lt">{{ $q->typeLabel() }}</span>
                                <span class="badge bg-warning-lt text-uppercase">{{ $q->difficulty }}</span>
                            </td>
                            <td class="small">
                                @if($q->grade_level)<span class="badge bg-secondary-lt">Kelas {{ $q->grade_level }}</span>@endif
                                @if($q->department)<span class="badge bg-purple-lt">{{ $q->department }}</span>@endif
                                <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                    {{ $q->teacher_id === auth()->id() ? 'Milik Anda' : 'Dibagikan: '.($q->teacher?->name ?? '-') }}
                                </div>
                            </td>
                            <td class="text-center small">
                                <div class="fw-bold">{{ $q->points }} poin</div>
                                @if($q->correct_answer)
                                    <div class="text-muted" style="font-size: 0.72rem;">Kunci: {{ \Illuminate\Support\Str::limit($q->correct_answer, 18) }}</div>
                                @endif
                                <div class="mt-1 d-flex justify-content-center gap-1 flex-wrap" style="font-size: 0.7rem;">
                                    <span class="badge bg-indigo-lt">Dipakai {{ $q->usage_count }}x</span>
                                    @if($q->accuracyPercent() !== null)
                                        <span class="badge bg-azure-lt">Benar {{ $q->accuracyPercent() }}%</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Tidak ada soal yang tersedia untuk filter ini (atau semua soal yang cocok sudah ditambahkan).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

@if($questions->hasPages())
    <div class="mt-3">{{ $questions->links() }}</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = Array.from(document.querySelectorAll('.js-picker-checkbox'));
    const countEl = document.getElementById('picker-selected-count');
    const pointsEl = document.getElementById('picker-selected-points');
    const submitBtn = document.getElementById('btn-submit-picker');

    const updateSummary = () => {
        let count = 0;
        let totalPoints = 0;
        checkboxes.forEach((cb) => {
            const row = cb.closest('tr');
            if (cb.checked) {
                count++;
                totalPoints += Number(cb.dataset.points || 0);
                row?.classList.add('table-primary');
            } else {
                row?.classList.remove('table-primary');
            }
        });

        if (countEl) countEl.textContent = String(count);
        if (pointsEl) pointsEl.textContent = String(Math.round(totalPoints * 100) / 100);
        if (submitBtn) submitBtn.disabled = count === 0;
    };

    document.querySelectorAll('.js-picker-row').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('input, a, button')) return;
            const cb = row.querySelector('.js-picker-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                updateSummary();
            }
        });
    });

    checkboxes.forEach((cb) => cb.addEventListener('change', updateSummary));

    document.getElementById('btn-select-all-page')?.addEventListener('click', () => {
        checkboxes.forEach((cb) => { cb.checked = true; });
        updateSummary();
    });

    document.getElementById('btn-clear-all-page')?.addEventListener('click', () => {
        checkboxes.forEach((cb) => { cb.checked = false; });
        updateSummary();
    });

    updateSummary();
});
</script>
@endpush
