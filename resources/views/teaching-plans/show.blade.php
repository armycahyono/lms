@extends('layouts.app')

@section('title', $plan->title)

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('teaching-plans.index') }}" class="text-decoration-none">Perangkat Ajar</a>
                &middot; {{ $plan->academicYear?->name ?? '-' }} &middot; Kelas {{ $plan->grade_level }} ({{ $plan->phase ?? '-' }})
            </div>
            <h2 class="page-title">{{ $plan->title }}</h2>
        </div>
        <div class="col-auto d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-blue-lt px-3 py-2">{{ $plan->subject?->name }}</span>
            <span class="badge bg-success-lt px-3 py-2">KKTP Min: {{ $plan->kktp_min_score }}</span>
            @if(auth()->user()->isAdmin() || $plan->teacher_id === auth()->id())
                <form action="{{ route('teaching-plans.toggle-share', $plan) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $plan->is_shared ? 'btn-success' : 'btn-outline-secondary' }}">
                        <i class="ti {{ $plan->is_shared ? 'ti-world' : 'ti-lock' }} me-1"></i>
                        {{ $plan->is_shared ? 'Dibagikan ke Mapel' : 'Privat (Hanya Saya)' }}
                    </button>
                </form>
                @if(!empty($copyablePlans) && $copyablePlans->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-copy-plan">
                        <i class="ti ti-copy me-1"></i> Salin Referensi / Tahun Lalu
                    </button>
                @endif
                <a href="{{ route('teaching-plans.template.download', $plan) }}" class="btn btn-sm btn-outline-success">
                    <i class="ti ti-file-spreadsheet me-1"></i> Unduh Template (.xlsx)
                </a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-import-excel">
                    <i class="ti ti-upload me-1"></i> Impor Excel
                </button>
            @else
                <span class="badge bg-azure-lt px-3 py-2"><i class="ti ti-eye me-1"></i> Mode Referensi (Read-Only)</span>
            @endif
        </div>
    </div>
</div>

@php
    $canEdit = auth()->user()->isAdmin() || $plan->teacher_id === auth()->id();
@endphp

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Bagian Perangkat Ajar">
        <button type="button" class="lms-pill-tab active js-tp-tab" data-target="tp-cp" role="tab" aria-selected="true">
            <i class="ti ti-target"></i>
            <span>CP, Prota &amp; KKTP</span>
        </button>
        <button type="button" class="lms-pill-tab js-tp-tab" data-target="tp-atp" role="tab" aria-selected="false">
            <i class="ti ti-list-numbers"></i>
            <span>ATP (Tujuan Pembelajaran)</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $plan->learningObjectives->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-tp-tab" data-target="tp-modul" role="tab" aria-selected="false">
            <i class="ti ti-file-description"></i>
            <span>RPP / Modul Ajar</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $plan->lessonPlans->count() }}</span>
        </button>
        <button type="button" class="lms-pill-tab js-tp-tab" data-target="tp-parallel" role="tab" aria-selected="false">
            <i class="ti ti-send"></i>
            <span>Terbitkan ke Kelas Paralel</span>
            <span class="badge bg-success-lt rounded-pill">{{ $parallelCourses->count() }}</span>
        </button>
    </div>
</div>

<div class="tab-content">
    {{-- Tab 1: CP, Prota, KKTP (Read-only by default) --}}
    <div class="tab-pane active" id="tp-cp">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Ringkasan CP, Program Tahunan &amp; KKTP</h3>
                @if($canEdit)
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-cp">
                        <i class="ti ti-edit me-1"></i> Edit CP, Prota &amp; KKTP
                    </button>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Capaian Pembelajaran (CP)</div>
                        <div class="p-3 rounded bg-light-subtle border">
                            {{ $plan->capaian_pembelajaran ?: 'Belum diisi.' }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Fase</div>
                        <div class="fw-semibold">{{ $plan->phase ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Minggu Efektif (Prota)</div>
                        <div class="fw-semibold">{{ $plan->effective_weeks ? $plan->effective_weeks . ' Minggu' : '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Target &amp; Alokasi JP</div>
                        <div class="fw-semibold">{{ $plan->total_jp ? $plan->total_jp . ' JP' : '-' }} <span class="text-muted small">(ATP: {{ $totalAllocatedJp }} JP)</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Pendekatan &amp; Skor KKTP</div>
                        <div class="fw-semibold text-capitalize">{{ $plan->kktp_type }} &middot; Min. {{ $plan->kktp_min_score }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Pedoman / Kriteria KKTP</div>
                        <div class="p-3 rounded bg-light-subtle border">
                            {{ $plan->kktp_notes ?: 'Belum ada catatan kriteria KKTP.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 2: ATP (Read-only table + Add/Edit Modals) --}}
    <div class="tab-pane d-none" id="tp-atp">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">Total alokasi terpetakan: <strong>{{ $totalAllocatedJp }} JP</strong></div>
            @if($canEdit)
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-tp">
                    <i class="ti ti-plus me-1"></i> Tambah Tujuan Pembelajaran
                </button>
            @endif
        </div>

        <div class="lms-table-card">
            <div class="table-responsive">
                <table class="table lms-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tujuan Pembelajaran</th>
                            <th class="text-center">JP</th>
                            <th>Periode</th>
                            @if($canEdit)<th class="text-end">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plan->learningObjectives as $obj)
                            <tr>
                                <td><span class="badge bg-blue-lt fw-bold">{{ $obj->code }}</span></td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $obj->title }}</div>
                                    @if($obj->description)
                                        <div class="small text-muted">{{ $obj->description }}</div>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ $obj->jp_allocation }} JP</td>
                                <td class="small text-muted">{{ $obj->target_month ?? '-' }}</td>
                                @if($canEdit)
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-tp-{{ $obj->id }}" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <form action="{{ route('teaching-plans.objectives.destroy', [$plan, $obj]) }}" method="POST" onsubmit="return confirm('Hapus Tujuan Pembelajaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canEdit ? 5 : 4 }}" class="text-center text-muted py-4">Belum ada Tujuan Pembelajaran di ATP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab 3: RPP / Modul Ajar (Read-only cards + Add/Edit Modals) --}}
    <div class="tab-pane d-none" id="tp-modul">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">Daftar RPP / Modul Ajar terstruktur</div>
            @if($canEdit)
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-lp">
                    <i class="ti ti-plus me-1"></i> Tambah Modul Ajar
                </button>
            @endif
        </div>

        <div class="d-grid gap-3">
            @forelse($plan->lessonPlans as $lp)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h4 class="mb-1">{{ $lp->title }}</h4>
                                @if($lp->learningObjective)
                                    <span class="badge bg-blue-lt">{{ $lp->learningObjective->code }}: {{ $lp->learningObjective->title }}</span>
                                @endif
                            </div>
                            <div class="d-flex gap-1">
                                @if($lp->external_url)
                                    <a href="{{ $lp->external_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info">
                                        <i class="ti ti-external-link me-1"></i> Tautan
                                    </a>
                                @endif
                                @if($lp->attachment_path)
                                    <a href="{{ route('teaching-plans.lesson-plans.download', [$plan, $lp]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-download me-1"></i> Unduh
                                    </a>
                                @endif
                                @if($canEdit)
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-edit-lp-{{ $lp->id }}" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form action="{{ route('teaching-plans.lesson-plans.destroy', [$plan, $lp]) }}" method="POST" onsubmit="return confirm('Hapus Modul Ajar ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if($lp->learning_goals)
                            <div class="small mb-2"><strong>Tujuan:</strong> {{ $lp->learning_goals }}</div>
                        @endif
                        @if($lp->activities)
                            <div class="small mb-2"><strong>Langkah Kegiatan:</strong> {{ $lp->activities }}</div>
                        @endif
                        @if($lp->assessment_plan)
                            <div class="small text-muted"><strong>Rencana Asesmen:</strong> {{ $lp->assessment_plan }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body text-center text-muted py-4">Belum ada RPP / Modul Ajar.</div></div>
            @endforelse
        </div>
    </div>

    {{-- Tab 4: Publish Parallel (Read-only list + Action Modal) --}}
    <div class="tab-pane d-none" id="tp-parallel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="ti ti-send me-1 text-primary"></i> Kelas Digital Paralel Terhubung</h3>
                @if($canEdit && $parallelCourses->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-publish-parallel">
                        <i class="ti ti-plus me-1"></i> Terbitkan Materi / LKPD Serentak
                    </button>
                @endif
            </div>
            <div class="card-body">
                @if($parallelCourses->isEmpty())
                    <div class="text-muted">Belum ada kelas digital paralel yang cocok pada Tahun Ajaran ini.</div>
                @else
                    <div class="row g-3">
                        @foreach($parallelCourses as $pc)
                            <div class="col-md-6">
                                <div class="p-3 border rounded d-flex justify-content-between align-items-center gap-2">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $pc->title }}</div>
                                        <div class="small text-muted">{{ $pc->classroom?->name }} &middot; Status: <span class="text-uppercase">{{ $pc->status }}</span></div>
                                    </div>
                                    <a href="{{ route('courses.show', $pc) }}" class="btn btn-sm btn-outline-primary">Buka Kelas</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canEdit)
    {{-- Modal Edit CP, Prota, KKTP --}}
    <div class="modal modal-blur fade" id="modal-edit-cp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.update', $plan) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit CP, Program Tahunan &amp; KKTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Judul Perangkat Ajar</label>
                            <input type="text" name="title" class="form-control" value="{{ $plan->title }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fase</label>
                            <input type="text" name="phase" class="form-control" value="{{ $plan->phase }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Capaian Pembelajaran (CP)</label>
                            <textarea name="capaian_pembelajaran" rows="4" class="form-control">{{ $plan->capaian_pembelajaran }}</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Minggu Efektif</label>
                            <input type="number" name="effective_weeks" class="form-control" value="{{ $plan->effective_weeks }}" min="1" max="60">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Target Total JP</label>
                            <input type="number" name="total_jp" class="form-control" value="{{ $plan->total_jp }}" min="1" max="1000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pendekatan KKTP</label>
                            <select name="kktp_type" class="form-select">
                                <option value="interval" @selected($plan->kktp_type === 'interval')>Interval Nilai</option>
                                <option value="deskripsi" @selected($plan->kktp_type === 'deskripsi')>Deskripsi Kriteria</option>
                                <option value="rubrik" @selected($plan->kktp_type === 'rubrik')>Rubrik Penilaian</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Skor Min. Tuntas</label>
                            <input type="number" name="kktp_min_score" class="form-control" value="{{ $plan->kktp_min_score }}" min="0" max="100" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pedoman / Kriteria KKTP</label>
                            <textarea name="kktp_notes" rows="3" class="form-control">{{ $plan->kktp_notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Tambah TP --}}
    <div class="modal modal-blur fade" id="modal-add-tp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.objectives.store', $plan) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Tujuan Pembelajaran (ATP)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label">Kode TP</label>
                                <input type="text" name="code" class="form-control" placeholder="TP-1" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label">Alokasi JP</label>
                                <input type="number" name="jp_allocation" class="form-control" value="4" min="1" max="200" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Tujuan Pembelajaran</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label">Target Bulan / Periode</label>
                            <input type="text" name="target_month" class="form-control" placeholder="Juli - Agustus">
                        </div>
                        <div>
                            <label class="form-label">Catatan / Materi Pokok</label>
                            <textarea name="description" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah ke ATP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit per TP --}}
    @foreach($plan->learningObjectives as $obj)
        <div class="modal modal-blur fade" id="modal-edit-tp-{{ $obj->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('teaching-plans.objectives.update', [$plan, $obj]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit {{ $obj->code }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div class="row g-2">
                                <div class="col-5">
                                    <label class="form-label">Kode TP</label>
                                    <input type="text" name="code" class="form-control" value="{{ $obj->code }}" required>
                                </div>
                                <div class="col-7">
                                    <label class="form-label">Alokasi JP</label>
                                    <input type="number" name="jp_allocation" class="form-control" value="{{ $obj->jp_allocation }}" min="1" max="200" required>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Tujuan Pembelajaran</label>
                                <input type="text" name="title" class="form-control" value="{{ $obj->title }}" required>
                            </div>
                            <div>
                                <label class="form-label">Target Bulan / Periode</label>
                                <input type="text" name="target_month" class="form-control" value="{{ $obj->target_month }}">
                            </div>
                            <div>
                                <label class="form-label">Catatan / Materi Pokok</label>
                                <textarea name="description" rows="2" class="form-control">{{ $obj->description }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Modal Tambah Modul Ajar --}}
    <div class="modal modal-blur fade" id="modal-add-lp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.lesson-plans.store', $plan) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah RPP / Modul Ajar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Judul Modul Ajar</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label">Terkait TP (ATP)</label>
                            <select name="learning_objective_id" class="form-select">
                                <option value="">-- Pilih TP (Opsional) --</option>
                                @foreach($plan->learningObjectives as $obj)
                                    <option value="{{ $obj->id }}">{{ $obj->code }} — {{ $obj->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tujuan Pembelajaran</label>
                            <textarea name="learning_goals" rows="2" class="form-control"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Langkah Kegiatan Pembelajaran</label>
                            <textarea name="activities" rows="3" class="form-control" placeholder="Pendahuluan, Inti, Penutup..."></textarea>
                        </div>
                        <div>
                            <label class="form-label">Rencana Asesmen</label>
                            <textarea name="assessment_plan" rows="2" class="form-control"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Lampiran Dokumen (PDF/DOC, Opsional)</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Lampiran Tautan Video / Materi Online / Drive (Opsional)</label>
                            <input type="url" name="external_url" class="form-control" placeholder="https://drive.google.com/... atau https://youtube.com/...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Modul Ajar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit per Modul Ajar --}}
    @foreach($plan->lessonPlans as $lp)
        <div class="modal modal-blur fade" id="modal-edit-lp-{{ $lp->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('teaching-plans.lesson-plans.update', [$plan, $lp]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit RPP / Modul Ajar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Modul Ajar</label>
                                <input type="text" name="title" class="form-control" value="{{ $lp->title }}" required>
                            </div>
                            <div>
                                <label class="form-label">Terkait TP (ATP)</label>
                                <select name="learning_objective_id" class="form-select">
                                    <option value="">-- Pilih TP (Opsional) --</option>
                                    @foreach($plan->learningObjectives as $obj)
                                        <option value="{{ $obj->id }}" @selected($lp->learning_objective_id === $obj->id)>{{ $obj->code }} — {{ $obj->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tujuan Pembelajaran</label>
                                <textarea name="learning_goals" rows="2" class="form-control">{{ $lp->learning_goals }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">Langkah Kegiatan Pembelajaran</label>
                                <textarea name="activities" rows="3" class="form-control">{{ $lp->activities }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">Rencana Asesmen</label>
                                <textarea name="assessment_plan" rows="2" class="form-control">{{ $lp->assessment_plan }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">Ganti Lampiran Dokumen (PDF/DOC, Opsional)</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Lampiran Tautan Video / Materi Online / Drive (Opsional)</label>
                                <input type="url" name="external_url" class="form-control" value="{{ $lp->external_url }}" placeholder="https://drive.google.com/... atau https://youtube.com/...">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Modal Terbitkan ke Kelas Paralel --}}
    @if($parallelCourses->isNotEmpty())
        <div class="modal modal-blur fade" id="modal-publish-parallel" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('teaching-plans.publish-parallel', $plan) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Terbitkan Bahan Ajar / LKPD ke Kelas Paralel</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-12">
                                <label class="form-label">Pilih Kelas Tujuan</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($parallelCourses as $pc)
                                        <label class="form-check">
                                            <input type="checkbox" name="course_ids[]" value="{{ $pc->id }}" class="form-check-input" checked>
                                            <span class="form-check-label fw-medium">{{ $pc->classroom?->name }} — {{ $pc->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Modul / Bab di Kelas</label>
                                <input type="text" name="module_title" class="form-control" placeholder="Bab 1: Pengenalan" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Judul Bahan Ajar / LKPD</label>
                                <input type="text" name="material_title" class="form-control" placeholder="LKPD Pertemuan 1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="category" class="form-select">
                                    <option value="materi">Materi</option>
                                    <option value="lkpd">LKPD</option>
                                    <option value="buku">Buku / Modul</option>
                                    <option value="presentasi">Presentasi</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Konten</label>
                                <select name="type" class="form-select">
                                    <option value="text">Teks</option>
                                    <option value="file">File</option>
                                    <option value="link">Tautan</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Isi Materi / Instruksi Singkat</label>
                                <textarea name="content" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">File Lampiran (Opsional)</label>
                                <input type="file" name="file" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tautan Eksternal (Opsional)</label>
                                <input type="url" name="external_url" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input type="checkbox" name="is_published" value="1" class="form-check-input" checked>
                                    <span class="form-check-label">Langsung terbitkan (Publish) ke siswa</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i> Terbitkan ke Kelas Terpilih
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endif

@if($canEdit && !empty($copyablePlans) && $copyablePlans->isNotEmpty())
    <div class="modal modal-blur fade" id="modal-copy-plan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.copy-from', $plan) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Salin dari Tahun Lalu / Referensi Mapel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Pilih Perangkat Sumber</label>
                        <select name="source_plan_id" class="form-select" required>
                            @foreach($copyablePlans as $cp)
                                <option value="{{ $cp->id }}">
                                    {{ $cp->title }} — {{ $cp->academicYear?->name }} ({{ $cp->teacher_id === auth()->id() ? 'Milik Saya' : $cp->teacher?->name }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">CP, Prota, KKTP, ATP, dan Modul Ajar akan disalin ke perangkat Anda dalam status Privat.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Salin Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canEdit)
    <div class="modal modal-blur fade" id="modal-import-excel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.import.preview', $plan) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Impor Perangkat Ajar dari Excel (.xlsx)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div class="alert alert-info py-2 small mb-0">
                            Belum punya formatnya? Unduh <a href="{{ route('teaching-plans.template.download', $plan) }}" class="fw-bold">Template Excel (.xlsx)</a>, isi secara offline, lalu unggah di sini untuk melihat pratinjau sebelum disimpan.
                        </div>
                        <div>
                            <label class="form-label">Pilih File Excel (.xlsx / .xls)</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-eye me-1"></i> Pratinjau Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.js-tp-tab');
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
});
</script>
@endpush
