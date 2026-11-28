@extends('layouts.app')

@section('title', 'Edit Paket Ujian: ' . $exam->title)

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('exams.show', $exam) }}" class="text-decoration-none">Kelola Ujian: {{ $exam->title }}</a>
            </div>
            <h2 class="page-title">Edit Pengaturan &amp; Kelas Paket Ujian (CBT)</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('exams.show', $exam) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

<form action="{{ route('exams.update', $exam) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Pengaturan Paket Ujian Sekolah</h3>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-7 d-grid gap-3">
                    <div>
                        <label class="form-label">Mapel &amp; Tingkat</label>
                        <input type="text" class="form-control bg-light" value="{{ $exam->course?->subject?->name ?? 'Mapel' }} — {{ $exam->course?->classroom?->grade_level ? 'Kelas ' . \App\Models\Classroom::parseGradeNumber($exam->course->classroom->grade_level, $exam->course->classroom->name) : ($exam->course?->classroom?->name ?? '') }}" readonly>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Judul Ujian</label>
                            <input type="text" name="title" value="{{ old('title', $exam->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Jenis Ujian</label>
                            <select name="exam_type" class="form-select" required>
                                @foreach(['PTS', 'PAS', 'US', 'Simulasi', 'Lainnya'] as $type)
                                    <option value="{{ $type }}" @selected(old('exam_type', $exam->exam_type) === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Ref Durasi</label>
                            <div class="input-group">
                                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="1" max="360" class="form-control" required>
                                <span class="input-group-text">menit</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Soal Tampil</label>
                            <input type="number" name="display_question_count" value="{{ old('display_question_count', $exam->display_question_count) }}" min="1" max="500" class="form-control" placeholder="Semua ({{ $exam->questions->count() }})">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kode Paket CBT</label>
                            <input type="text" name="cbt_exam_code" value="{{ old('cbt_exam_code', $exam->cbt_exam_code) }}" class="form-control" placeholder="Otomatis">
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Kelas Paralel dalam Paket</span>
                        <span class="badge bg-blue-lt">{{ count($selectedCourseIds) }} / {{ $eligibleCourses->count() }} kelas</span>
                    </label>
                    <div class="border rounded p-3 bg-light-subtle d-grid gap-2" style="max-height: 250px; overflow-y: auto;">
                        @foreach($eligibleCourses as $course)
                            @php
                                $isSelected = in_array($course->id, old('course_ids', $selectedCourseIds), false);
                                $isLocked = in_array($course->id, $lockedCourseIds, false);
                            @endphp
                            <label class="form-check mb-0 d-flex align-items-center justify-content-between gap-2">
                                <span>
                                    <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" class="form-check-input" @checked($isSelected || $isLocked)>
                                    <span class="form-check-label fw-medium">{{ $course->classroom?->name ?? $course->title }}</span>
                                </span>
                                @if($isLocked)
                                    <span class="badge bg-warning-lt" title="Sudah dirilis ke CBTPGRI atau sudah ada nilai masuk, tidak bisa dilepas">Terkunci</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    <div class="form-hint mt-2">
                        Kelas baru yang dicentang otomatis menyalin seluruh soal ({{ $exam->questions->count() }} soal) dari paket ini.
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('exams.show', $exam) }}" class="btn btn-link link-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan Paket
            </button>
        </div>
    </div>
</form>
@endsection
