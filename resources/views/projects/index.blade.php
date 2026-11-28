@extends('layouts.app')

@section('title', 'Projek / Kokurikuler')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Kokurikuler' }}
            </div>
            <h2 class="page-title">Modul Projek / Kokurikuler</h2>
        </div>
        @if($canManage)
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-project">
                    <i class="ti ti-plus me-1"></i> Tambah Modul Projek
                </button>
            </div>
        @endif
    </div>
</div>

<div class="d-grid gap-3">
    @forelse($projects as $project)
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="badge bg-purple-lt">{{ $project->theme }}</span>
                            @if($project->phase)
                                <span class="badge bg-blue-lt">{{ $project->phase }}</span>
                            @endif
                        </div>
                        <h3 class="card-title mb-0">{{ $project->title }}</h3>
                        <div class="small text-muted">Koordinator: {{ $project->coordinator?->name ?? '-' }}</div>
                    </div>
                    <div class="d-flex gap-1">
                        @if($project->attachment_path)
                            <a href="{{ route('projects.download', $project) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-download me-1"></i> Unduh
                            </a>
                        @endif
                        @if($canManage && (auth()->user()->isAdmin() || $project->coordinator_id === auth()->id()))
                            <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Hapus Modul Projek ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($project->dimensions)
                    <div class="small mb-2"><strong>Dimensi:</strong> {{ $project->dimensions }}</div>
                @endif
                @if($project->objectives)
                    <div class="small mb-2"><strong>Tujuan:</strong> {{ $project->objectives }}</div>
                @endif
                @if($project->activities)
                    <div class="small mb-2"><strong>Aktivitas:</strong> {{ $project->activities }}</div>
                @endif
                @if($project->assessment_notes)
                    <div class="small text-muted"><strong>Asesmen:</strong> {{ $project->assessment_notes }}</div>
                @endif
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-sparkles-off fs-1 d-block mb-2 opacity-50"></i>
                Belum ada Modul Projek / Kokurikuler pada Tahun Ajaran ini.
            </div>
        </div>
    @endforelse
</div>

@if($canManage)
    <div class="modal modal-blur fade" id="modal-add-project" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Modul Projek / Kokurikuler</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Judul Projek</label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: Pengolahan Limbah Plastik" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-8">
                                <label class="form-label">Tema</label>
                                <input type="text" name="theme" class="form-control" placeholder="Gaya Hidup Berkelanjutan" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Fase</label>
                                <input type="text" name="phase" class="form-control" placeholder="Fase E">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dimensi Profil Pelajar</label>
                            <input type="text" name="dimensions" class="form-control" placeholder="Gotong Royong, Kreatif, Bernalar Kritis">
                        </div>
                        <div>
                            <label class="form-label">Tujuan Projek</label>
                            <textarea name="objectives" rows="2" class="form-control"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Alur Aktivitas Projek</label>
                            <textarea name="activities" rows="3" class="form-control" placeholder="Tahap Pengenalan, Kontekstualisasi, Aksi, Refleksi"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Asesmen Projek</label>
                            <textarea name="assessment_notes" rows="2" class="form-control"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Dokumen Modul Projek (PDF/DOC, Opsional)</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Modul Projek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
