@extends('layouts.app')

@section('title', 'Tugas')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Penugasan &amp; Praktik Siswa</div>
            <h2 class="page-title">Daftar Tugas</h2>
        </div>
        @if($mode !== 'student' && $manageableCourses->isNotEmpty())
            <div class="col-auto d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-batch-assignment">
                    <i class="ti ti-clipboard-plus me-1"></i> Buat Tugas
                </button>
                <a href="{{ route('exams.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-device-desktop-check me-1"></i> Buka Kuis &amp; Ujian
                </a>
            </div>
        @endif
    </div>
</div>

<div>
    {{-- Daftar Tugas --}}
    <div id="tab-tasks">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            @if($mode !== 'student' && ($filterSubjects->isNotEmpty() || $filterGrades->isNotEmpty()))
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select class="form-select form-select-sm js-filter-subject" style="width: auto; min-width: 170px;" aria-label="Filter Mata Pelajaran">
                        <option value="">Semua Mapel</option>
                        @foreach($filterSubjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm js-filter-grade" style="width: auto; min-width: 130px;" aria-label="Filter Tingkat Kelas">
                        <option value="">Semua Tingkat</option>
                        @foreach($filterGrades as $gr)
                            <option value="{{ $gr }}">Kelas {{ $gr }}</option>
                        @endforeach
                    </select>
                    <input type="search" class="form-control form-control-sm js-filter-search" style="width: 190px;" placeholder="Cari judul tugas..." aria-label="Cari judul tugas">
                </div>
            @else
                <div></div>
            @endif

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group" role="group" aria-label="Filter tugas">
                    <a href="{{ route('tasks.index', ['tab' => 'tasks']) }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
                    @if($mode === 'student')
                        <a href="{{ route('tasks.index', ['tab' => 'tasks', 'filter' => 'pending']) }}" class="btn btn-sm {{ $filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Belum Dikerjakan</a>
                        <a href="{{ route('tasks.index', ['tab' => 'tasks', 'filter' => 'submitted']) }}" class="btn btn-sm {{ $filter === 'submitted' ? 'btn-primary' : 'btn-outline-secondary' }}">Sudah Dikumpulkan</a>
                    @elseif($mode === 'teacher')
                        <a href="{{ route('tasks.index', ['tab' => 'tasks', 'filter' => 'ungraded']) }}" class="btn btn-sm {{ $filter === 'ungraded' ? 'btn-primary' : 'btn-outline-secondary' }}">Perlu Dinilai</a>
                    @endif
                </div>
                <div class="btn-group" role="group" aria-label="Mode tampilan tugas">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary active js-view-mode" data-mode="card" title="Tampilan Kartu" aria-label="Tampilan Kartu" aria-pressed="true">
                        <i class="ti ti-layout-grid"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary js-view-mode" data-mode="list" title="Tampilan List" aria-label="Tampilan List" aria-pressed="false">
                        <i class="ti ti-list"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row row-cards js-view-card">
            @if($mode === 'student')
                @forelse($assignments as $assignment)
                    @php
                        $submission = $assignment->submissions->first();
                        $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $assignment->course?->subject?->name ?? 'Mata Pelajaran' }}</span>
                                        <span class="badge bg-azure-lt text-uppercase">{{ $assignment->assessment_type ?? 'formatif' }}</span>
                                    </div>
                                    @if($submission && $submission->graded_at)
                                        <span class="badge bg-success-lt">Dinilai: {{ $submission->score }}</span>
                                    @elseif($submission)
                                        <span class="badge bg-primary-lt">Sudah Dikumpulkan</span>
                                    @elseif($isOverdue)
                                        <span class="badge bg-danger-lt">Terlambat</span>
                                    @else
                                        <span class="badge bg-warning-lt">Belum Dikerjakan</span>
                                    @endif
                                </div>

                                <h3 class="card-title mb-1">{{ $assignment->title }}</h3>
                                <div class="text-muted small mb-3">
                                    {{ $assignment->course?->title }}
                                    @if($assignment->course?->classroom)
                                        &middot; {{ $assignment->course->classroom->name }}
                                    @endif
                                </div>

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="ti ti-calendar-event me-1"></i>
                                        {{ $assignment->due_at ? $assignment->due_at->translatedFormat('d M Y, H:i') : 'Tanpa batas waktu' }}
                                    </div>
                                    <a href="{{ route('courses.assignments.show', [$assignment->course_id, $assignment]) }}" class="btn btn-sm btn-outline-primary">
                                        Buka
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-clipboard-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada tugas pada filter ini.
                            </div>
                        </div>
                    </div>
                @endforelse
            @else
                @forelse($assignmentGroups as $pkg)
                    @php
                        $firstAssignment = $pkg['first_assignment'];
                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                    @endphp
                    <div class="col-md-6 col-lg-4 js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $pkg['subject_name'] }}@if($pkg['grade_level']) &middot; Kelas {{ $pkg['grade_level'] }}@endif</span>
                                        <span class="badge bg-azure-lt text-uppercase">{{ $pkg['assessment_type'] }}</span>
                                        <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}">
                                            {{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas
                                        </span>
                                    </div>
                                    @if($pkg['ungraded_submissions_count'] > 0)
                                        <span class="badge bg-warning-lt">{{ $pkg['ungraded_submissions_count'] }} Perlu Dinilai</span>
                                    @else
                                        <span class="badge bg-success-lt">Semua Dinilai</span>
                                    @endif
                                </div>

                                <h3 class="card-title mb-2">{{ $pkg['title'] }}</h3>

                                <div class="mb-2">
                                    <div class="text-muted small mb-1">Kelas Penerima:</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($pkg['assignments'] as $asg)
                                            <a href="{{ route('courses.assignments.show', [$asg->course_id, $asg]) }}" class="badge bg-azure-lt text-decoration-none">
                                                {{ $asg->course?->classroom?->name ?? $asg->course?->title }}
                                                @if($asg->ungraded_submissions_count > 0)
                                                    <span class="text-warning ms-1">({{ $asg->ungraded_submissions_count }})</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                @if($pkg['missing_courses']->isNotEmpty())
                                    <div class="text-warning small mb-2">
                                        <i class="ti ti-alert-triangle me-1"></i>Belum diterapkan ke: {{ $pkg['missing_courses']->map(fn ($c) => $c->classroom?->name ?? $c->title)->implode(', ') }}
                                    </div>
                                @endif

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center gap-2">
                                    <div class="text-muted small">
                                        <i class="ti ti-calendar-event me-1"></i>
                                        {{ $pkg['due_at'] ? $pkg['due_at']->translatedFormat('d M Y, H:i') : 'Tanpa batas waktu' }}
                                        &middot; {{ $pkg['total_submissions'] }} dikumpulkan
                                    </div>
                                    <a href="{{ route('courses.assignments.show', [$firstAssignment->course_id, $firstAssignment]) }}" class="btn btn-sm btn-outline-primary">
                                        Periksa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-clipboard-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada tugas pada filter ini.
                            </div>
                        </div>
                    </div>
                @endforelse
            @endif
        </div>

        @if($mode === 'student')
            <div class="card js-view-list d-none">
                <div class="table-responsive">
                    <table class="table lms-table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Judul Tugas</th>
                                <th>Mapel &amp; Kelas</th>
                                <th>Jenis</th>
                                <th>Tenggat</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                @php
                                    $submission = $assignment->submissions->first();
                                    $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $assignment->title }}</td>
                                    <td class="small text-muted">{{ $assignment->course?->subject?->name ?? 'Mata Pelajaran' }} &middot; {{ $assignment->course?->classroom?->name }}</td>
                                    <td><span class="badge bg-azure-lt text-uppercase">{{ $assignment->assessment_type ?? 'formatif' }}</span></td>
                                    <td class="small text-muted">{{ $assignment->due_at ? $assignment->due_at->translatedFormat('d M Y, H:i') : 'Tanpa batas waktu' }}</td>
                                    <td>
                                        @if($submission && $submission->graded_at)
                                            <span class="badge bg-success-lt">Dinilai: {{ $submission->score }}</span>
                                        @elseif($submission)
                                            <span class="badge bg-primary-lt">Sudah Dikumpulkan</span>
                                        @elseif($isOverdue)
                                            <span class="badge bg-danger-lt">Terlambat</span>
                                        @else
                                            <span class="badge bg-warning-lt">Belum Dikerjakan</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('courses.assignments.show', [$assignment->course_id, $assignment]) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tugas pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="vstack gap-4 js-view-list d-none">
                @forelse($assignmentGradeGroups as $gradeGrp)
                    <div class="js-grade-section" data-grade="{{ $gradeGrp['grade_level'] ?? '' }}">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                                {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Lainnya' }}
                            </span>
                            <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel</span>
                        </div>
                        <div class="vstack gap-3">
                            @foreach($gradeGrp['subjects'] as $sgGroup)
                                <div class="card js-filterable-group" data-subject-id="{{ $sgGroup['subject_id'] }}" data-grade="{{ $sgGroup['grade_level'] ?? '' }}">
                                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <h3 class="card-title mb-0">{{ $sgGroup['subject_name'] }}</h3>
                                            <span class="badge bg-blue-lt">{{ $sgGroup['packages']->count() }} Tugas</span>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table lms-table table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Judul Tugas</th>
                                                    <th>Jenis</th>
                                                    <th>Kelas Penerima &amp; Cakupan</th>
                                                    <th>Tenggat &amp; Progres</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sgGroup['packages'] as $pkg)
                                                    @php
                                                        $firstAssignment = $pkg['first_assignment'];
                                                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                                                    @endphp
                                                    <tr class="js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                                                        <td class="fw-semibold">{{ $pkg['title'] }}</td>
                                                        <td><span class="badge bg-azure-lt text-uppercase">{{ $pkg['assessment_type'] }}</span></td>
                                                        <td>
                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}">{{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas</span>
                                                                @foreach($pkg['assignments'] as $asg)
                                                                    <a href="{{ route('courses.assignments.show', [$asg->course_id, $asg]) }}" class="badge bg-azure-lt text-decoration-none">
                                                                        {{ $asg->course?->classroom?->name ?? $asg->course?->title }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                            @if($pkg['missing_courses']->isNotEmpty())
                                                                <div class="text-warning small mt-1">Belum: {{ $pkg['missing_courses']->map(fn ($c) => $c->classroom?->name ?? $c->title)->implode(', ') }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            <div class="text-muted">{{ $pkg['due_at'] ? $pkg['due_at']->translatedFormat('d M Y, H:i') : 'Tanpa batas waktu' }}</div>
                                                            <div>
                                                                {{ $pkg['total_submissions'] }} dikumpulkan
                                                                @if($pkg['ungraded_submissions_count'] > 0)
                                                                    &middot; <span class="text-warning fw-semibold">{{ $pkg['ungraded_submissions_count'] }} perlu dinilai</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="{{ route('courses.assignments.show', [$firstAssignment->course_id, $firstAssignment]) }}" class="btn btn-sm btn-outline-primary">Periksa</a>
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
                        <div class="card-body text-center text-muted py-4">Belum ada tugas pada filter ini.</div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>

@if($mode !== 'student' && $manageableCourses->isNotEmpty())
    {{-- Modal Buat Tugas (Bisa Banyak Kelas) --}}
    <div class="modal modal-blur fade" id="modal-batch-assignment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('tasks.store-batch') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Tugas Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Pilih Kelas Digital Tujuan</label>
                            <div class="border rounded p-2 d-flex flex-wrap gap-3" style="max-height: 140px; overflow-y: auto;">
                                @foreach($manageableCourses as $mc)
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="course_ids[]" value="{{ $mc->id }}" class="form-check-input">
                                        <span class="form-check-label small fw-medium">{{ $mc->title }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-hint">Anda dapat mencentang lebih dari satu kelas paralel sekaligus.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label">Judul Tugas</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis Asesmen</label>
                                <select name="assessment_type" class="form-select">
                                    <option value="formatif">Formatif</option>
                                    <option value="diagnostik">Diagnostik</option>
                                    <option value="sumatif">Sumatif</option>
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
                        <button type="submit" class="btn btn-primary">Terbitkan Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif
@endsection

@push('scripts')
<style>
.js-view-mode.active {
    background-color: var(--tblr-primary, #206bc4);
    border-color: var(--tblr-primary, #206bc4);
    color: #fff;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewButtons = document.querySelectorAll('.js-view-mode');
    const cardViews = document.querySelectorAll('.js-view-card');
    const listViews = document.querySelectorAll('.js-view-list');
    const storageKey = 'lms_tasks_view_mode';

    const applyViewMode = (mode) => {
        const isList = mode === 'list';
        cardViews.forEach((el) => el.classList.toggle('d-none', isList));
        listViews.forEach((el) => el.classList.toggle('d-none', !isList));
        viewButtons.forEach((btn) => {
            const active = btn.dataset.mode === mode;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const defaultMode = @json($mode === 'student' ? 'card' : 'list');
    const storedMode = localStorage.getItem(storageKey);
    const savedMode = (storedMode === 'list' || storedMode === 'card') ? storedMode : defaultMode;
    applyViewMode(savedMode);

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode === 'list' ? 'list' : 'card';
            localStorage.setItem(storageKey, mode);
            applyViewMode(mode);
        });
    });

    const subjectSelect = document.querySelector('.js-filter-subject');
    const gradeSelect = document.querySelector('.js-filter-grade');
    const searchInput = document.querySelector('.js-filter-search');
    const filterItems = document.querySelectorAll('.js-filterable-item');
    const filterGroups = document.querySelectorAll('.js-filterable-group');

    const applyFilters = () => {
        const subjectVal = subjectSelect?.value || '';
        const gradeVal = gradeSelect?.value || '';
        const searchVal = (searchInput?.value || '').trim().toLowerCase();

        filterItems.forEach((el) => {
            const matchSubject = !subjectVal || el.dataset.subjectId === subjectVal;
            const matchGrade = !gradeVal || el.dataset.grade === gradeVal;
            const matchSearch = !searchVal || (el.dataset.title || '').includes(searchVal);
            el.classList.toggle('d-none', !(matchSubject && matchGrade && matchSearch));
        });

        filterGroups.forEach((grp) => {
            const visibleRows = grp.querySelectorAll('.js-filterable-item:not(.d-none)').length;
            grp.classList.toggle('d-none', visibleRows === 0);
        });

        document.querySelectorAll('.js-grade-section').forEach((sec) => {
            const visibleCards = sec.querySelectorAll('.js-filterable-group:not(.d-none)').length;
            sec.classList.toggle('d-none', visibleCards === 0);
        });
    };

    subjectSelect?.addEventListener('change', applyFilters);
    gradeSelect?.addEventListener('change', applyFilters);
    searchInput?.addEventListener('input', applyFilters);
});
</script>
@endpush
