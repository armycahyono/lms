@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Kembali ke {{ $course->title }}
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h3 mb-1">{{ $assignment->title }}</h2>
        <div class="text-muted small mb-3">
            Tenggat: {{ $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : 'Tidak dibatasi' }} &middot;
            Skor Maksimal: {{ $assignment->max_score }}
        </div>
        @if($assignment->instructions)
            <div class="mb-3">{!! nl2br(e($assignment->instructions)) !!}</div>
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
        <div class="card-header"><h3 class="card-title mb-0">Pengumpulan Jawaban</h3></div>
        <div class="card-body">
            @if($mySubmission)
                <div class="alert alert-info mb-3">
                    Dikumpulkan pada {{ $mySubmission->submitted_at?->format('d M Y H:i') }}
                    @if($mySubmission->is_late) <span class="badge bg-warning">Terlambat</span> @endif
                    @if($mySubmission->graded_at)
                        <div class="mt-2">
                            <strong>Nilai: {{ $mySubmission->score }} / {{ $assignment->max_score }}</strong>
                            @if($mySubmission->feedback)
                                <div class="small mt-1">Umpan balik: {{ $mySubmission->feedback }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <form action="{{ route('courses.assignments.submissions.store', [$course, $assignment]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Jawaban Teks</label>
                    <textarea name="content" rows="4" class="form-control">{{ old('content', $mySubmission?->content) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unggah File Jawaban (Private Storage)</label>
                    <input type="file" name="file" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Kirim Jawaban</button>
            </form>
        </div>
    </div>
@elseif($canManage)
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Daftar Pengumpulan Siswa ({{ $submissions->count() }})</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Siswa</th><th>Jawaban</th><th>Nilai &amp; Validasi SIAP</th></tr></thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $submission->student?->name }}</div>
                                <div class="text-muted small">{{ $submission->submitted_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                @if($submission->content)
                                    <div class="small mb-1">{{ \Illuminate\Support\Str::limit($submission->content, 120) }}</div>
                                @endif
                                @if($submission->file_path)
                                    <a href="{{ route('courses.assignments.submissions.download', [$course, $assignment, $submission]) }}" class="btn btn-sm btn-outline-primary">
                                        Unduh File
                                    </a>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('courses.assignments.submissions.grade', [$course, $assignment, $submission]) }}" method="POST" class="row g-2 align-items-center">
                                    @csrf
                                    <div class="col-auto"><input type="number" step="0.01" name="score" value="{{ $submission->score }}" class="form-control form-control-sm" style="width: 90px;" min="0" max="{{ $assignment->max_score }}" required></div>
                                    <div class="col"><input type="text" name="feedback" value="{{ $submission->feedback }}" class="form-control form-control-sm" placeholder="Umpan balik"></div>
                                    <div class="col-auto">
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_validated_for_siap" value="1" @checked($submission->is_validated_for_siap)>
                                            <span class="form-check-label small">Validasi SIAP</span>
                                        </label>
                                    </div>
                                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-success">Simpan</button></div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada pengumpulan siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
