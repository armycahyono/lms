@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">{{ $course->classroom?->name }} &middot; {{ $course->subject?->name }}</div>
            <h2 class="page-title">{{ $course->title }}</h2>
        </div>
        @if($canManage)
            <div class="col-auto d-flex gap-2">
                @if($course->status === 'draft')
                    <form action="{{ route('courses.open', $course) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-player-play me-1"></i> Buka Kelas &amp; Auto-Enroll Siswa
                        </button>
                    </form>
                @elseif($course->status === 'active')
                    <form action="{{ route('courses.archive', $course) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Arsipkan Kelas</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-8">
        @if($canManage)
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Tambah Modul Baru</h3></div>
                <div class="card-body">
                    <form action="{{ route('courses.modules.store', $course) }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-md-5"><input type="text" name="title" class="form-control" placeholder="Judul Bab / Modul" required></div>
                        <div class="col-md-5"><input type="text" name="description" class="form-control" placeholder="Deskripsi singkat (opsional)"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
                    </form>
                </div>
            </div>
        @endif

        @forelse($course->modules as $module)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">{{ $module->title }}</h3>
                        @if($module->description)
                            <div class="text-muted small">{{ $module->description }}</div>
                        @endif
                    </div>
                    @if($canManage)
                        <form action="{{ route('courses.modules.toggle-publish', [$course, $module]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $module->is_published ? 'btn-success-lt' : 'btn-warning-lt' }}">
                                {{ $module->is_published ? 'Dipublikasi' : 'Draft' }}
                            </button>
                        </form>
                    @endif
                </div>
                <div class="list-group list-group-flush">
                    @forelse($module->materials as $material)
                        <a href="{{ route('courses.materials.show', [$course, $material]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="ti {{ $material->type === 'file' ? 'ti-file-download' : ($material->type === 'video_link' ? 'ti-brand-youtube' : 'ti-file-text') }} me-2 text-primary"></i>
                                <span>{{ $material->title }}</span>
                            </div>
                            @if(in_array($material->id, $completedMaterialIds, true))
                                <span class="badge bg-success-lt">Selesai</span>
                            @endif
                        </a>
                    @empty
                        <div class="list-group-item text-muted small">Belum ada materi pada modul ini.</div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="card mb-3"><div class="card-body text-muted text-center py-4">Belum ada modul pembelajaran.</div></div>
        @endforelse

        @if($unassignedMaterials->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Materi Lainnya</h3></div>
                <div class="list-group list-group-flush">
                    @foreach($unassignedMaterials as $material)
                        <a href="{{ route('courses.materials.show', [$course, $material]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="ti {{ $material->type === 'file' ? 'ti-file-download' : ($material->type === 'video_link' ? 'ti-brand-youtube' : 'ti-file-text') }} me-2 text-primary"></i>
                                <span>{{ $material->title }}</span>
                            </div>
                            @if(in_array($material->id, $completedMaterialIds, true))
                                <span class="badge bg-success-lt">Selesai</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Daftar Tugas</h3></div>
            <div class="list-group list-group-flush">
                @forelse($course->assignments as $assignment)
                    <a href="{{ route('courses.assignments.show', [$course, $assignment]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $assignment->title }}</div>
                            <div class="text-muted small">
                                Tenggat: {{ $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : 'Tidak dibatasi' }} &middot; Skor Maks: {{ $assignment->max_score }}
                            </div>
                        </div>
                        <span class="badge {{ $assignment->is_published ? 'bg-blue-lt' : 'bg-warning-lt' }}">
                            {{ $assignment->is_published ? 'Buka' : 'Draft' }}
                        </span>
                    </a>
                @empty
                    <div class="list-group-item text-muted small">Belum ada tugas.</div>
                @endforelse
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Kuis Harian</h3></div>
            <div class="list-group list-group-flush">
                @forelse($course->quizzes as $quiz)
                    <a href="{{ route('courses.quizzes.show', [$course, $quiz]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $quiz->title }}</div>
                            <div class="text-muted small">Durasi: {{ $quiz->duration_minutes }} menit &middot; Maks {{ $quiz->max_attempts }}x</div>
                        </div>
                        <span class="badge {{ $quiz->is_published ? 'bg-blue-lt' : 'bg-warning-lt' }}">
                            {{ $quiz->is_published ? 'Buka' : 'Draft' }}
                        </span>
                    </a>
                @empty
                    <div class="list-group-item text-muted small">Belum ada kuis harian.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Forum Diskusi Kelas</h3></div>
            <div class="card-body">
                <form action="{{ route('courses.discussions.store', $course) }}" method="POST" class="mb-4">
                    @csrf
                    <textarea name="body" rows="2" class="form-control mb-2" placeholder="Tulis pertanyaan atau diskusi kelas..." required></textarea>
                    <button type="submit" class="btn btn-sm btn-primary">Kirim Diskusi</button>
                </form>

                @forelse($course->discussions as $discussion)
                    <div class="border rounded p-3 mb-2 bg-light-subtle">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $discussion->user?->name }}</strong>
                            <span class="text-muted small">{{ $discussion->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="mb-2 mt-1">{{ $discussion->body }}</p>
                        @foreach($discussion->replies as $reply)
                            <div class="ms-4 mt-2 ps-3 border-start">
                                <div class="small"><strong>{{ $reply->user?->name }}</strong> &middot; <span class="text-muted">{{ $reply->created_at?->diffForHumans() }}</span></div>
                                <div class="small">{{ $reply->body }}</div>
                            </div>
                        @endforeach
                        <form action="{{ route('courses.discussions.store', $course) }}" method="POST" class="mt-2 d-flex gap-2">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $discussion->id }}">
                            <input type="text" name="body" class="form-control form-control-sm" placeholder="Balas..." required>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Balas</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada diskusi di kelas ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if($canManage)
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Tambah Materi</h3></div>
                <div class="card-body">
                    <form action="{{ route('courses.materials.store', $course) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Modul</label>
                            <select name="module_id" class="form-select form-select-sm">
                                <option value="">Tanpa Modul</option>
                                @foreach($course->modules as $module)
                                    <option value="{{ $module->id }}">{{ $module->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Judul Materi</label>
                            <input type="text" name="title" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Tipe</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="text">Teks / Catatan</option>
                                <option value="file">File Dokumen (Private)</option>
                                <option value="video_link">Link Video YouTube</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Konten Teks</label>
                            <textarea name="content" rows="3" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">File Lampiran (opsional)</label>
                            <input type="file" name="file" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">URL Video (opsional)</label>
                            <input type="url" name="external_url" class="form-control form-control-sm" placeholder="https://youtube.com/...">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan Materi</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Buat Tugas Baru</h3></div>
                <div class="card-body">
                    <form action="{{ route('courses.assignments.store', $course) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2"><input type="text" name="title" class="form-control form-control-sm" placeholder="Judul Tugas" required></div>
                        <div class="mb-2"><textarea name="instructions" rows="2" class="form-control form-control-sm" placeholder="Instruksi pengerjaan"></textarea></div>
                        <div class="mb-2"><input type="datetime-local" name="due_at" class="form-control form-control-sm"></div>
                        <div class="mb-2"><input type="number" name="max_score" value="100" class="form-control form-control-sm" min="1" max="1000" required></div>
                        <div class="mb-3"><input type="file" name="attachment" class="form-control form-control-sm"></div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Buat Tugas</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Buat Kuis Harian</h3></div>
                <div class="card-body">
                    <form action="{{ route('courses.quizzes.store', $course) }}" method="POST">
                        @csrf
                        <div class="mb-2"><input type="text" name="title" class="form-control form-control-sm" placeholder="Judul Kuis" required></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><input type="number" name="duration_minutes" value="30" class="form-control form-control-sm" min="1" placeholder="Durasi (menit)" required></div>
                            <div class="col-6"><input type="number" name="max_attempts" value="1" class="form-control form-control-sm" min="1" placeholder="Maks Attempt" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Buat Kuis</button>
                    </form>
                </div>
            </div>

            @if($duplicableCourses->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Duplikasi Materi dari Kelas Lain</h3></div>
                    <div class="card-body">
                        <form action="{{ route('courses.duplicate', $course) }}" method="POST">
                            @csrf
                            <select name="source_course_id" class="form-select form-select-sm mb-2" required>
                                @foreach($duplicableCourses as $source)
                                    <option value="{{ $source->id }}">{{ $source->title }} ({{ $source->academicYear?->name }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Duplikasi Modul &amp; Materi</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
