@extends('layouts.app')

@section('title', 'Perangkat Ajar')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Kurikulum & Perencanaan' }}
            </div>
            <h2 class="page-title">Master Perangkat Ajar</h2>
        </div>
        @if($subjectGradeOptions->isNotEmpty())
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-plan">
                    <i class="ti ti-plus me-1"></i> Buat / Buka Perangkat Mapel
                </button>
            </div>
        @endif
    </div>
</div>

<div class="vstack gap-4">
    @forelse($planGradeGroups as $gradeGrp)
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                    {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Umum / Lainnya' }}
                </span>
                <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_plans'] }} Perangkat</span>
            </div>
            <div class="vstack gap-3">
                @foreach($gradeGrp['subjects'] as $subjGrp)
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="card-title mb-0">{{ $subjGrp['subject_name'] }}</h3>
                                <span class="badge bg-blue-lt">{{ $subjGrp['plans']->count() }} Perangkat</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table lms-table table-vcenter mb-0">
                                <thead>
                                    <tr>
                                        <th>Perangkat Ajar</th>
                                        <th class="text-center">ATP (TP)</th>
                                        <th class="text-center">Modul Ajar</th>
                                        <th class="text-center">Visibilitas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjGrp['plans'] as $plan)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $plan->title }}</div>
                                                <div class="small text-muted">
                                                    {{ $plan->phase ?? '-' }}
                                                    @if($isLeadership)
                                                        &middot; {{ $plan->teacher?->name ?? '-' }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center fw-semibold">{{ $plan->learning_objectives_count }}</td>
                                            <td class="text-center fw-semibold">{{ $plan->lesson_plans_count }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $plan->is_shared ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                                    {{ $plan->is_shared ? 'Dibagikan' : 'Privat' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('teaching-plans.show', $plan) }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-eye me-1"></i> Lihat Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-notebook-off fs-1 d-block mb-2 opacity-50"></i>
                Belum ada Master Perangkat Ajar pada Tahun Ajaran ini.
            </div>
        </div>
    @endforelse
</div>

@if(!empty($sharedPlans) && $sharedPlans->isNotEmpty())
    <div class="mt-4">
        <h3 class="h4 mb-2">Referensi Perangkat Mapel (Dibagikan Rekan Guru)</h3>
        <div class="lms-table-card">
            <div class="table-responsive">
                <table class="table lms-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Perangkat Ajar</th>
                            <th>Pemilik &amp; Tahun</th>
                            <th class="text-center">ATP / Modul</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sharedPlans as $sp)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sp->title }}</div>
                                    <div class="small text-muted">{{ $sp->subject?->name }} &middot; Kelas {{ $sp->grade_level }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-medium">{{ $sp->teacher?->name ?? '-' }}</div>
                                    <div class="text-muted">{{ $sp->academicYear?->name ?? '-' }}</div>
                                </td>
                                <td class="text-center small">{{ $sp->learning_objectives_count }} TP / {{ $sp->lesson_plans_count }} Modul</td>
                                <td class="text-end">
                                    <a href="{{ route('teaching-plans.show', $sp) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye me-1"></i> Lihat Referensi
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if($subjectGradeOptions->isNotEmpty())
    <div class="modal modal-blur fade" id="modal-create-plan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('teaching-plans.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat / Buka Perangkat Mapel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Mata Pelajaran &amp; Tingkat</label>
                            <select name="subject_grade" class="form-select" required>
                                @foreach($subjectGradeOptions as $opt)
                                    <option value="{{ $opt['subject_id'] }}:{{ $opt['grade_level'] }}">
                                        {{ $opt['subject_name'] }} — Kelas {{ $opt['grade_level'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Satu perangkat ajar berlaku untuk seluruh rombel paralel pada tingkat ini.</div>
                        </div>
                        <div>
                            <label class="form-label">Fase</label>
                            <input type="text" name="phase" class="form-control" placeholder="Otomatis (Fase E / Fase F)">
                        </div>
                        <div>
                            <label class="form-label">Judul Perangkat (Opsional)</label>
                            <input type="text" name="title" class="form-control" placeholder="Otomatis dari mapel &amp; tingkat">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-folder-plus me-1"></i> Kelola Perangkat Ajar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
