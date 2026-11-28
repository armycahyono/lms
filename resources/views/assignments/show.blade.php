@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span class="badge bg-blue-lt">{{ $course->title }}</span>
                    <span class="badge {{ $assignment->is_published ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                        {{ $assignment->is_published ? 'Terbit' : 'Draft' }}
                    </span>
                </div>
                <h2 class="h2 mb-1">{{ $assignment->title }}</h2>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-secondary-lt px-3 py-2">Skor Maksimal: {{ $assignment->max_score }}</span>
                @if($canManage ?? \Illuminate\Support\Facades\Gate::allows('manage', $course))
                    @php
                        $hasSubmissions = $assignment->submissions()->exists();
                        $canDeleteAssignment = ! $hasSubmissions && (! $assignment->is_published || auth()->user()->isAdmin());
                    @endphp
                    @if(! $hasSubmissions)
                        <form action="{{ route('courses.assignments.toggle-publish', [$course, $assignment]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                {{ $assignment->is_published ? 'Jadikan Draft' : 'Publikasikan' }}
                            </button>
                        </form>
                    @endif
                    @if($canDeleteAssignment)
                        <form action="{{ route('courses.assignments.destroy', [$course, $assignment]) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Tugas">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        <div class="text-muted small mb-3">
            <i class="ti ti-calendar-event me-1"></i>
            Tenggat: {{ $assignment->due_at ? $assignment->due_at->translatedFormat('d M Y, H:i') : 'Tidak dibatasi' }}
        </div>

        @if($assignment->instructions)
            <div class="bg-light rounded p-3 mb-3" style="line-height: 1.7;">
                {!! nl2br(e($assignment->instructions)) !!}
            </div>
        @endif

        @if($assignment->attachment_path)
            <a href="{{ route('courses.assignments.attachment', [$course, $assignment]) }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-download me-1"></i> Unduh Lampiran Soal
            </a>
        @endif
    </div>
</div>

@if(auth()->user()->isSiswa())
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Pengumpulan Jawaban</h3>
            @if($mySubmission && $mySubmission->graded_at)
                <span class="badge bg-success-lt">Sudah Dinilai</span>
            @elseif($mySubmission)
                <span class="badge bg-primary-lt">Sudah Dikumpulkan</span>
            @else
                <span class="badge bg-warning-lt">Belum Dikumpulkan</span>
            @endif
        </div>
        <div class="card-body">
            @if($mySubmission)
                <div class="alert {{ $mySubmission->graded_at ? 'alert-success' : 'alert-info' }} mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            Dikumpulkan pada <strong>{{ $mySubmission->submitted_at?->translatedFormat('d M Y, H:i') }}</strong>
                            @if($mySubmission->is_late)
                                <span class="badge bg-warning ms-1">Terlambat</span>
                            @endif
                        </div>
                        @if($mySubmission->graded_at)
                            <div class="fs-4 fw-bold">Nilai: {{ $mySubmission->score }} / {{ $assignment->max_score }}</div>
                        @endif
                    </div>
                    @if($mySubmission->graded_at && $mySubmission->feedback)
                        <div class="small mt-2 pt-2 border-top">
                            <strong>Umpan Balik Guru:</strong> {{ $mySubmission->feedback }}
                        </div>
                    @endif
                </div>
            @endif

            <form action="{{ route('courses.assignments.submissions.store', [$course, $assignment]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="content">Jawaban Teks</label>
                    <textarea id="content" name="content" rows="4" class="form-control" placeholder="Tulis jawaban atau catatan Anda di sini...">{{ old('content', $mySubmission?->content) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="file">Unggah File Jawaban (Opsional, Private Storage)</label>
                    <input id="file" type="file" name="file" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-send me-1"></i> {{ $mySubmission ? 'Perbarui Jawaban' : 'Kirim Jawaban' }}
                </button>
            </form>
        </div>
    </div>
@elseif($canManage)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Daftar Pengumpulan Siswa ({{ $submissions->count() }})</h3>
        </div>
        <div class="list-group list-group-flush">
            @forelse($submissions as $submission)
                <div class="list-group-item p-3 p-md-4">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-5">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold">{{ $submission->student?->name }}</span>
                                @if($submission->graded_at)
                                    <span class="badge bg-success-lt">Nilai: {{ $submission->score }}</span>
                                @else
                                    <span class="badge bg-warning-lt">Perlu Dinilai</span>
                                @endif
                            </div>
                            <div class="text-muted small mb-2">
                                Dikumpulkan: {{ $submission->submitted_at?->translatedFormat('d M Y, H:i') }}
                            </div>
                            @if($submission->content)
                                <div class="bg-light rounded p-2 small mb-2">{{ $submission->content }}</div>
                            @endif
                            @if($submission->file_path)
                                <a href="{{ route('courses.assignments.submissions.download', [$course, $assignment, $submission]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-download me-1"></i> Unduh File Jawaban
                                </a>
                            @endif
                        </div>
                        <div class="col-lg-7">
                            <form action="{{ route('courses.assignments.submissions.grade', [$course, $assignment, $submission]) }}" method="POST" class="row g-2 align-items-center">
                                @csrf
                                <div class="col-sm-3">
                                    <label class="form-label small mb-1">Nilai</label>
                                    <input type="number" step="0.01" name="score" value="{{ $submission->score }}" class="form-control form-control-sm" min="0" max="{{ $assignment->max_score }}" required>
                                </div>
                                <div class="col-sm-5">
                                    <label class="form-label small mb-1">Umpan Balik</label>
                                    <input type="text" name="feedback" value="{{ $submission->feedback }}" class="form-control form-control-sm" placeholder="Catatan untuk siswa">
                                </div>
                                <div class="col-sm-4 d-flex flex-column gap-2 pt-sm-3">
                                    <label class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_validated_for_siap" value="1" @checked($submission->is_validated_for_siap)>
                                        <span class="form-check-label small">Validasi SIAP</span>
                                    </label>
                                    <button type="submit" class="btn btn-sm btn-success w-100">Simpan Nilai</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">Belum ada pengumpulan siswa.</div>
            @endforelse
        </div>
    </div>
@endif
@endsection
