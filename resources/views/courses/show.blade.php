@extends('layouts.app')

@section('title', $course->title)

@section('content')
@php
    $totalMaterialsInCourse = $course->modules->sum(fn ($m) => $m->materials->count()) + $unassignedMaterials->count();
    $completedInCourse = count($completedMaterialIds);
    $courseProgressPct = $totalMaterialsInCourse > 0 ? (int) round(($completedInCourse / $totalMaterialsInCourse) * 100) : 0;
@endphp

<div class="mb-3">
    <a href="{{ route('courses.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Semua Kelas
    </a>
</div>

<div class="card mb-4 overflow-hidden">
    <div style="height: 8px; background: var(--lms-gradient);"></div>
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-blue-lt">{{ $course->classroom?->name ?? 'Kelas' }}</span>
                    <span class="badge bg-secondary-lt">{{ $course->subject?->name ?? 'Mata Pelajaran' }}</span>
                    <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : ($course->status === 'draft' ? 'bg-warning-lt' : 'bg-secondary-lt') }} text-uppercase">
                        {{ $course->status }}
                    </span>
                </div>
                <h2 class="mb-1">{{ $course->title }}</h2>
                <div class="text-muted small">
                    Pengajar: {{ $course->teachers->pluck('name')->join(', ') ?: '-' }}
                    @if($course->academicYear)
                        &middot; Tahun Ajaran {{ $course->academicYear->name }}
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                @if(auth()->user()->isSiswa())
                    <div class="bg-light rounded p-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Progres Belajar</span>
                            <span class="fw-semibold text-primary">{{ $completedInCourse }}/{{ $totalMaterialsInCourse }} ({{ $courseProgressPct }}%)</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-primary" style="width: {{ $courseProgressPct }}%"></div>
                        </div>
                    </div>
                @elseif($canManage)
                    <div class="d-flex justify-content-lg-end gap-2">
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
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        @if($canManage)
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-module">
                    <i class="ti ti-folder-plus me-1"></i> Tambah Modul
                </button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-material">
                    <i class="ti ti-book-upload me-1"></i> Tambah Materi
                </button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-assignment">
                    <i class="ti ti-clipboard-plus me-1"></i> Buat Tugas
                </button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-quiz">
                    <i class="ti ti-file-plus me-1"></i> Buat Kuis
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-course-announcement">
                    <i class="ti ti-speakerphone me-1"></i> Pengumuman
                </button>
                @if($duplicableCourses->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-duplicate-course">
                        <i class="ti ti-copy me-1"></i> Duplikasi Materi
                    </button>
                @endif
            </div>
        @endif

        @if($course->announcements->isNotEmpty())
            <div class="card mb-3 border-primary">
                <div class="card-header py-2 bg-blue-lt">
                    <h3 class="card-title mb-0 text-primary"><i class="ti ti-speakerphone me-1"></i> Pengumuman Kelas</h3>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($course->announcements->take(3) as $announcement)
                        <div class="list-group-item py-2 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold small">
                                    @if($announcement->is_pinned)<i class="ti ti-pin text-primary me-1"></i>@endif
                                    {{ $announcement->title }}
                                </div>
                                <div class="text-muted small">{{ $announcement->body }}</div>
                            </div>
                            @if($canManage)
                                <form action="{{ route('announcements.destroy', $announcement) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mb-3 overflow-auto">
            <div class="lms-pill-tabs" role="tablist" aria-label="Menu Kelas">
                <button type="button" class="lms-pill-tab active js-course-tab" data-target="tab-materials" role="tab" aria-selected="true">
                    <i class="ti ti-book"></i>
                    <span>Materi &amp; Modul</span>
                </button>
                <button type="button" class="lms-pill-tab js-course-tab" data-target="tab-assignments" role="tab" aria-selected="false">
                    <i class="ti ti-clipboard-check"></i>
                    <span>Tugas</span>
                    <span class="badge bg-blue-lt rounded-pill">{{ $course->assignments->count() }}</span>
                </button>
                <button type="button" class="lms-pill-tab js-course-tab" data-target="tab-quizzes" role="tab" aria-selected="false">
                    <i class="ti ti-file-check"></i>
                    <span>Kuis</span>
                    <span class="badge bg-blue-lt rounded-pill">{{ $course->quizzes->count() }}</span>
                </button>
                <button type="button" class="lms-pill-tab js-course-tab" data-target="tab-discussions" role="tab" aria-selected="false">
                    <i class="ti ti-messages"></i>
                    <span>Diskusi</span>
                </button>
                <button type="button" class="lms-pill-tab js-course-tab" data-target="tab-attendance" role="tab" aria-selected="false">
                    <i class="ti ti-notebook"></i>
                    <span>Jurnal &amp; Kehadiran</span>
                </button>
                @if($canManage)
                    <button type="button" class="lms-pill-tab js-course-tab" data-target="tab-remedial" role="tab" aria-selected="false">
                        <i class="ti ti-award"></i>
                        <span>KKTP &amp; Remedial</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="tab-content">
                    {{-- Tab Materi & Modul --}}
                    <div class="tab-pane active show" id="tab-materials" role="tabpanel">
                        @forelse($course->modules as $module)
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center bg-light-subtle">
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
                                    @forelse($module->materials as $mIdx => $material)
                                        @php $isDone = in_array($material->id, $completedMaterialIds, true); @endphp
                                        <a href="{{ route('courses.materials.show', [$course, $material]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ $isDone ? 'bg-success-lt' : '' }}">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="lms-step-dot {{ $isDone ? 'bg-success text-white' : 'bg-blue-lt text-primary' }}">
                                                    @if($isDone)
                                                        <i class="ti ti-check"></i>
                                                    @else
                                                        {{ $mIdx + 1 }}
                                                    @endif
                                                </span>
                                                <div>
                                                    <div class="fw-semibold text-dark d-flex align-items-center gap-2 flex-wrap">
                                                        <span>{{ $material->title }}</span>
                                                        @if(($material->category ?? 'materi') !== 'materi')
                                                            <span class="badge bg-purple-lt text-uppercase" style="font-size: 0.65rem;">{{ $material->category }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="ti {{ $material->type === 'file' ? 'ti-file-download' : ($material->type === 'video_link' ? 'ti-brand-youtube' : 'ti-file-text') }} me-1"></i>
                                                        {{ $material->type === 'file' ? 'Dokumen' : ($material->type === 'video_link' ? 'Video' : 'Bacaan') }}
                                                    </div>
                                                </div>
                                            </div>
                                            @if($isDone)
                                                <span class="badge bg-success text-white">Selesai</span>
                                            @else
                                                <span class="btn btn-sm btn-outline-primary">Pelajari</span>
                                            @endif
                                        </a>
                                    @empty
                                        <div class="list-group-item text-muted small">Belum ada materi pada modul ini.</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-4">Belum ada modul pembelajaran.</div>
                        @endforelse

                        @if($unassignedMaterials->isNotEmpty())
                            <div class="card mt-3">
                                <div class="card-header"><h3 class="card-title mb-0">Materi Lainnya</h3></div>
                                <div class="list-group list-group-flush">
                                    @foreach($unassignedMaterials as $material)
                                        <a href="{{ route('courses.materials.show', [$course, $material]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar avatar-xs bg-blue-lt text-primary">
                                                    <i class="ti {{ $material->type === 'file' ? 'ti-file-download' : ($material->type === 'video_link' ? 'ti-brand-youtube' : 'ti-file-text') }}"></i>
                                                </span>
                                                <span class="fw-medium">{{ $material->title }}</span>
                                            </div>
                                            @if(in_array($material->id, $completedMaterialIds, true))
                                                <span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>Selesai</span>
                                            @else
                                                <i class="ti ti-chevron-right text-muted"></i>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Tab Tugas --}}
                    <div class="tab-pane" id="tab-assignments" role="tabpanel">
                        <div class="list-group list-group-flush">
                            @forelse($course->assignments as $assignment)
                                <a href="{{ route('courses.assignments.show', [$course, $assignment]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ $assignment->title }}</span>
                                            <span class="badge bg-azure-lt text-uppercase">{{ $assignment->assessment_type ?? 'formatif' }}</span>
                                        </div>
                                        <div class="text-muted small">
                                            Tenggat: {{ $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : 'Tidak dibatasi' }} &middot; Skor Maks: {{ $assignment->max_score }}
                                        </div>
                                    </div>
                                    <span class="badge {{ $assignment->is_published ? 'bg-blue-lt' : 'bg-warning-lt' }}">
                                        {{ $assignment->is_published ? 'Buka' : 'Draft' }}
                                    </span>
                                </a>
                            @empty
                                <div class="text-muted text-center py-4">Belum ada tugas pada kelas ini.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tab Kuis --}}
                    <div class="tab-pane" id="tab-quizzes" role="tabpanel">
                        <div class="list-group list-group-flush">
                            @forelse($course->quizzes as $quiz)
                                <a href="{{ route('courses.quizzes.show', [$course, $quiz]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ $quiz->title }}</span>
                                            <span class="badge bg-azure-lt text-uppercase">{{ $quiz->assessment_type ?? 'formatif' }}</span>
                                        </div>
                                        <div class="text-muted small">Durasi: {{ $quiz->duration_minutes }} menit &middot; Maks {{ $quiz->max_attempts }}x</div>
                                    </div>
                                    <span class="badge {{ $quiz->is_published ? 'bg-blue-lt' : 'bg-warning-lt' }}">
                                        {{ $quiz->is_published ? 'Buka' : 'Draft' }}
                                    </span>
                                </a>
                            @empty
                                <div class="text-muted text-center py-4">Belum ada kuis harian pada kelas ini.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tab Diskusi --}}
                    <div class="tab-pane" id="tab-discussions" role="tabpanel">
                        <form action="{{ route('courses.discussions.store', $course) }}" method="POST" class="mb-4">
                            @csrf
                            <textarea name="body" rows="2" class="form-control mb-2" placeholder="Tulis pertanyaan atau pengumuman kelas..." required></textarea>
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
                            <p class="text-muted small text-center py-3 mb-0">Belum ada diskusi di kelas ini.</p>
                        @endforelse
                    </div>

                    {{-- Tab Jurnal Mengajar & Kehadiran Pembelajaran Digital --}}
                    <div class="tab-pane" id="tab-attendance" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="alert alert-info py-2 small mb-0 flex-grow-1">
                                <i class="ti ti-info-circle me-1"></i>
                                Kehadiran pembelajaran digital tercatat <strong>otomatis</strong> saat siswa membuka kelas digital atau materi selama sesi aktif. Presensi tatap muka tetap dikelola melalui SIAP.
                            </div>
                            @if($canManage)
                                <button type="button" class="btn btn-sm btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#modal-add-session">
                                    <i class="ti ti-plus me-1"></i> Buka Sesi &amp; Jurnal
                                </button>
                            @endif
                        </div>

                        <div class="list-group list-group-flush">
                            @forelse($course->learningSessions as $session)
                                @php
                                    $myAttendance = auth()->user()->isSiswa() ? $session->attendances->first() : null;
                                    $accepting = $session->isAcceptingCheckIn();
                                @endphp
                                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ $session->title }}</span>
                                            @if($session->learningObjective)
                                                <span class="badge bg-blue-lt">{{ $session->learningObjective->code }}</span>
                                            @endif
                                            @if($session->is_achieved)
                                                <span class="badge bg-success-lt">Tercapai</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            {{ $session->session_date?->translatedFormat('d M Y') }}
                                            @if($session->topic) &middot; {{ $session->topic }} @endif
                                            @if($canManage)
                                                &middot; <span class="text-primary fw-medium">{{ $session->attendances_count }} siswa aktif</span>
                                            @endif
                                        </div>
                                        @if($session->journal_summary)
                                            <div class="small text-dark mt-1">{{ $session->journal_summary }}</div>
                                        @endif
                                        @if($session->reflection)
                                            <div class="small text-muted">Refleksi: {{ $session->reflection }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        @if(auth()->user()->isSiswa())
                                            @if($myAttendance)
                                                <span class="badge bg-success-lt px-3 py-2">
                                                    <i class="ti ti-check me-1"></i>
                                                    {{ $myAttendance->source === 'auto_digital' ? 'Hadir Otomatis' : 'Hadir' }} ({{ $myAttendance->checked_in_at?->format('H:i') }})
                                                </span>
                                            @elseif($accepting)
                                                <form action="{{ route('courses.sessions.check-in', [$course, $session]) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="ti ti-hand-click me-1"></i> Hadir di Sesi Ini
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-secondary-lt">Ditutup</span>
                                            @endif
                                        @elseif($canManage)
                                            <form action="{{ route('courses.sessions.toggle', [$course, $session]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $session->is_open ? 'btn-success-lt' : 'btn-secondary-lt' }}">
                                                    {{ $session->is_open ? 'Terbuka (Klik Tutup)' : 'Tertutup (Klik Buka)' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted text-center py-4 small">Belum ada jurnal &amp; sesi pembelajaran digital.</div>
                            @endforelse
                        </div>
                    </div>

                    @if($canManage)
                        {{-- Tab KKTP, Remedial & Pengayaan --}}
                        <div class="tab-pane" id="tab-remedial" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <span class="badge bg-success-lt px-3 py-2">Standar KKTP Kelas: {{ $kktpMinScore }}</span>
                                    @if($teachingPlan)
                                        <a href="{{ route('teaching-plans.show', $teachingPlan) }}" class="btn btn-sm btn-link">Lihat Master Perangkat Ajar</a>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-remedial">
                                    <i class="ti ti-plus me-1"></i> Catat Remedial / Pengayaan
                                </button>
                            </div>

                            @if($studentKktpSummary->isNotEmpty())
                                <div class="lms-table-card mb-4">
                                    <div class="table-responsive">
                                        <table class="table lms-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Siswa</th>
                                                    <th class="text-center">Rata-rata Nilai</th>
                                                    <th class="text-center">Status KKTP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($studentKktpSummary as $row)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $row['student']->name }}</td>
                                                        <td class="text-center">{{ $row['avg_score'] ?? '-' }}</td>
                                                        <td class="text-center">
                                                            @if($row['is_passed'] === true)
                                                                <span class="badge bg-success-lt">Tuntas</span>
                                                            @elseif($row['is_passed'] === false)
                                                                <span class="badge bg-warning-lt">Perlu Remedial</span>
                                                            @else
                                                                <span class="badge bg-secondary-lt">Belum Ada Nilai</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <div class="list-group list-group-flush">
                                @forelse($remedialEnrichments as $re)
                                    <div class="list-group-item d-flex justify-content-between align-items-center gap-2 py-2">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $re->student?->name ?? '-' }}
                                                <span class="badge {{ $re->program_type === 'remedial' ? 'bg-warning-lt' : 'bg-success-lt' }} text-uppercase ms-1">
                                                    {{ $re->program_type }}
                                                </span>
                                                @if($re->learningObjective)
                                                    <span class="badge bg-blue-lt ms-1">{{ $re->learningObjective->code }}</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                Awal: {{ $re->initial_score ?? '-' }} &rarr; Akhir: {{ $re->final_score ?? '-' }}
                                                @if($re->activity_notes) &middot; {{ $re->activity_notes }} @endif
                                            </div>
                                        </div>
                                        <form action="{{ route('courses.remedial-enrichments.destroy', [$course, $re]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="text-muted text-center py-4 small">Belum ada catatan program remedial atau pengayaan.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($canManage)
        {{-- Modal Tambah Modul --}}
        <div class="modal modal-blur fade" id="modal-add-module" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.modules.store', $course) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Modul / Bab</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Bab / Modul</label>
                                <input type="text" name="title" class="form-control" placeholder="Judul Bab / Modul Baru" required>
                            </div>
                            <div>
                                <label class="form-label">Deskripsi Singkat (Opsional)</label>
                                <input type="text" name="description" class="form-control" placeholder="Deskripsi singkat">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Tambah Modul</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Pengumuman Kelas --}}
        <div class="modal modal-blur fade" id="modal-course-announcement" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.announcements.store', $course) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Pengumuman Kelas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Pengumuman</label>
                                <input type="text" name="title" class="form-control" placeholder="Judul Pengumuman" required>
                            </div>
                            <div>
                                <label class="form-label">Isi Pengumuman</label>
                                <textarea name="body" rows="3" class="form-control" placeholder="Isi pengumuman untuk siswa..." required></textarea>
                            </div>
                            <label class="form-check">
                                <input type="checkbox" name="is_pinned" value="1" class="form-check-input" checked>
                                <span class="form-check-label">Sematkan di atas kelas</span>
                            </label>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Terbitkan Pengumuman</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Materi --}}
        <div class="modal modal-blur fade" id="modal-add-material" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.materials.store', $course) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Materi / Bahan Ajar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Modul</label>
                                <select name="module_id" class="form-select">
                                    <option value="">Tanpa Modul</option>
                                    @foreach($course->modules as $module)
                                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Judul Materi</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Tipe</label>
                                    <select name="type" class="form-select">
                                        <option value="text">Teks / Catatan</option>
                                        <option value="file">File Dokumen (Private)</option>
                                        <option value="video_link">Link Video YouTube</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Kategori</label>
                                    <select name="category" class="form-select">
                                        <option value="materi">Materi</option>
                                        <option value="lkpd">LKPD</option>
                                        <option value="buku">Buku / Modul</option>
                                        <option value="presentasi">Presentasi</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Konten Teks</label>
                                <textarea name="content" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">File Lampiran (Opsional)</label>
                                    <input type="file" name="file" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">URL Video (Opsional)</label>
                                    <input type="url" name="external_url" class="form-control" placeholder="https://youtube.com/...">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Materi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Buat Tugas --}}
        <div class="modal modal-blur fade" id="modal-add-assignment" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.assignments.store', $course) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Tugas Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Tugas</label>
                                <input type="text" name="title" class="form-control" placeholder="Judul Tugas" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Jenis Asesmen</label>
                                    <select name="assessment_type" class="form-select">
                                        <option value="formatif">Formatif</option>
                                        <option value="diagnostik">Diagnostik</option>
                                        <option value="sumatif">Sumatif</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">TP Terkait (Opsional)</label>
                                    <select name="learning_objective_id" class="form-select">
                                        <option value="">-- Pilih TP --</option>
                                        @foreach($learningObjectives as $obj)
                                            <option value="{{ $obj->id }}">{{ $obj->code }} — {{ $obj->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Instruksi Pengerjaan</label>
                                <textarea name="instructions" rows="2" class="form-control"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Rubrik / Pedoman Penskoran (Opsional)</label>
                                <textarea name="rubric_notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label">Tenggat</label>
                                    <input type="datetime-local" name="due_at" class="form-control">
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Skor Maksimal</label>
                                    <input type="number" name="max_score" value="100" class="form-control" min="1" max="1000" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label">Lampiran (Opsional)</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Buat Tugas</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Buat Kuis --}}
        <div class="modal modal-blur fade" id="modal-add-quiz" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.quizzes.store', $course) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Kuis Harian</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Kuis</label>
                                <input type="text" name="title" class="form-control" placeholder="Judul Kuis" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Jenis Asesmen</label>
                                    <select name="assessment_type" class="form-select">
                                        <option value="formatif">Formatif</option>
                                        <option value="diagnostik">Diagnostik</option>
                                        <option value="sumatif">Sumatif</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">TP Terkait (Opsional)</label>
                                    <select name="learning_objective_id" class="form-select">
                                        <option value="">-- Pilih TP --</option>
                                        @foreach($learningObjectives as $obj)
                                            <option value="{{ $obj->id }}">{{ $obj->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Tipe Kuis</label>
                                    <select name="quiz_type" class="form-select">
                                        <option value="reguler">Reguler</option>
                                        <option value="remedial">Remedial</option>
                                        <option value="pengayaan">Pengayaan</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">KKM</label>
                                    <input type="number" step="1" name="passing_score" value="75" class="form-control">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Durasi (Menit)</label>
                                    <input type="number" name="duration_minutes" value="30" class="form-control" min="1" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Maks Percobaan</label>
                                    <input type="number" name="max_attempts" value="1" class="form-control" min="1" required>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label">Kuis Induk (Opsional, Saring &lt; KKM)</label>
                                    <select name="parent_quiz_id" class="form-select">
                                        <option value="">-- Tidak Mengacu Kuis Induk --</option>
                                        @foreach($course->quizzes as $pq)
                                            <option value="{{ $pq->id }}">{{ $pq->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 pt-md-4">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="target_remedial_only" value="1" class="form-check-input">
                                        <span class="form-check-label small">Kunci hanya siswa &lt; KKM</span>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="shuffle_questions" value="1" class="form-check-input">
                                    <span class="form-check-label">Acak Soal</span>
                                </label>
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="shuffle_options" value="1" class="form-check-input">
                                    <span class="form-check-label">Acak Opsi</span>
                                </label>
                                <div class="ms-auto" style="min-width: 220px;">
                                    <input type="number" name="display_question_count" min="1" max="500" class="form-control form-control-sm" placeholder="Jml Soal Tampil (Acak dari Pool)">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Buat Kuis</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Buka Sesi & Jurnal --}}
        <div class="modal modal-blur fade" id="modal-add-session" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.sessions.store', $course) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Simpan Jurnal Mengajar &amp; Buka Sesi Digital</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Judul Pertemuan</label>
                                <input type="text" name="title" class="form-control" placeholder="Pertemuan 1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="session_date" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TP dari ATP (Opsional)</label>
                                <select name="learning_objective_id" class="form-select">
                                    <option value="">-- Pilih TP --</option>
                                    @foreach($learningObjectives as $obj)
                                        <option value="{{ $obj->id }}">{{ $obj->code }} — {{ $obj->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Topik / Materi Pokok</label>
                                <input type="text" name="topic" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ringkasan Jurnal Mengajar</label>
                                <textarea name="journal_summary" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Refleksi / Kendala Kelas</label>
                                <textarea name="reflection" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="is_achieved" value="1" class="form-check-input" checked>
                                    <span class="form-check-label">Tujuan pembelajaran tercapai</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Jurnal &amp; Buka Sesi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Catat Remedial / Pengayaan --}}
        <div class="modal modal-blur fade" id="modal-add-remedial" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('courses.remedial-enrichments.store', $course) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Catat Program Remedial / Pengayaan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Siswa</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- Pilih Siswa --</option>
                                    @foreach($courseStudents as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Jenis Program</label>
                                    <select name="program_type" class="form-select" required>
                                        <option value="remedial">Remedial (Belum Tuntas)</option>
                                        <option value="pengayaan">Pengayaan (Tuntas Optimal)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">TP Terkait (Opsional)</label>
                                    <select name="learning_objective_id" class="form-select">
                                        <option value="">-- Pilih TP --</option>
                                        @foreach($learningObjectives as $obj)
                                            <option value="{{ $obj->id }}">{{ $obj->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Nilai Awal</label>
                                    <input type="number" step="0.01" name="initial_score" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Nilai Akhir</label>
                                    <input type="number" step="0.01" name="final_score" class="form-control">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Catatan Tindak Lanjut</label>
                                <textarea name="activity_notes" rows="2" class="form-control" placeholder="Bimbingan ulang & tugas perbaikan"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Duplikasi Materi --}}
        @if($duplicableCourses->isNotEmpty())
            <div class="modal modal-blur fade" id="modal-duplicate-course" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('courses.duplicate', $course) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Duplikasi Materi dari Kelas Lain</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label">Pilih Kelas Sumber</label>
                                <select name="source_course_id" class="form-select" required>
                                    @foreach($duplicableCourses as $source)
                                        <option value="{{ $source->id }}">{{ $source->title }} ({{ $source->academicYear?->name }})</option>
                                    @endforeach
                                </select>
                                <div class="form-hint">Modul dan materi akan disalin dalam status Draft agar dapat diperiksa sebelum diterbitkan.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Duplikasi Modul &amp; Materi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.js-course-tab');
    const panes = document.querySelectorAll('.tab-content .tab-pane');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');

            panes.forEach((p) => p.classList.remove('active', 'show'));
            document.getElementById(tab.dataset.target)?.classList.add('active', 'show');
        });
    });
});
</script>
@endpush
