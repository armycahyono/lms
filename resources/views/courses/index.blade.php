@extends('layouts.app')

@section('title', 'Kelas Digital')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Pembelajaran Aktif</div>
            <h2 class="page-title">Daftar Kelas Digital</h2>
        </div>
    </div>
</div>

@if($homeroomCourses->isNotEmpty())
    <div class="mb-3 overflow-auto">
        <div class="lms-pill-tabs" role="tablist" aria-label="Kategori Kelas Digital">
            <button type="button" class="lms-pill-tab active js-courses-tab" data-target="courses-taught" role="tab" aria-selected="true">
                <i class="ti ti-chalkboard"></i>
                <span>Kelas yang Diampu</span>
                <span class="badge bg-blue-lt rounded-pill">{{ $taughtCourses->count() }}</span>
            </button>
            <button type="button" class="lms-pill-tab js-courses-tab" data-target="courses-homeroom" role="tab" aria-selected="false">
                <i class="ti ti-shield-check"></i>
                <span>Kelas Sebagai Wali</span>
                <span class="badge bg-success-lt rounded-pill">{{ $homeroomCourses->count() }}</span>
            </button>
        </div>
    </div>

    <div class="tab-content">
        <div class="tab-pane active" id="courses-taught">
            @if(! auth()->user()->isSiswa() && ($filterSubjects->isNotEmpty() || $filterGrades->isNotEmpty()))
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-sm-4 col-md-3">
                                <select class="form-select form-select-sm js-course-filter-subject" aria-label="Filter Mata Pelajaran">
                                    <option value="">Semua Mata Pelajaran</option>
                                    @foreach($filterSubjects as $subj)
                                        <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-sm-3 col-md-2">
                                <select class="form-select form-select-sm js-course-filter-grade" aria-label="Filter Tingkat Kelas">
                                    <option value="">Semua Tingkat</option>
                                    @foreach($filterGrades as $gr)
                                        <option value="{{ $gr }}">Kelas {{ $gr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(auth()->user()->isSiswa())
                <div class="row row-cards">
                    @forelse($taughtCourses as $course)
                        @include('courses.partials.course-card', ['course' => $course, 'completedCountByCourse' => $completedCountByCourse, 'isHomeroomTab' => false])
                    @empty
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center text-muted py-5">
                                    <i class="ti ti-books-off fs-1 d-block mb-2 opacity-50"></i>
                                    Belum ada kelas digital yang Anda ikuti pada Tahun Ajaran ini.
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="vstack gap-4">
                    @forelse($taughtGradeGroups as $gradeGrp)
                        <div class="js-grade-section" data-grade="{{ $gradeGrp['grade_level'] ?? '' }}">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                                    {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Lainnya' }}
                                </span>
                                <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_courses'] }} Rombel</span>
                            </div>
                            <div class="vstack gap-3">
                                @foreach($gradeGrp['subjects'] as $grp)
                                    <div class="card js-course-group" data-subject-id="{{ $grp['subject_id'] }}" data-grade="{{ $grp['grade_level'] ?? '' }}">
                                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <h3 class="card-title mb-0">{{ $grp['subject_name'] }}</h3>
                                                <span class="badge bg-blue-lt">{{ $grp['courses']->count() }} Rombel</span>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="ti ti-users me-1"></i>{{ $grp['students_count'] }} Siswa
                                                &middot; Maks {{ $grp['max_materials'] }} Materi
                                                &middot; Maks {{ $grp['max_assignments'] }} Tugas
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table lms-table table-vcenter mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Kelas</th>
                                                        <th>Siswa</th>
                                                        <th>Materi Terbit</th>
                                                        <th>Tugas</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($grp['courses'] as $course)
                                                        @php
                                                            $lagsBehind = $grp['courses']->count() > 1 && (
                                                                $course->published_materials_count < $grp['max_materials']
                                                                || $course->assignments_count < $grp['max_assignments']
                                                            );
                                                        @endphp
                                                        <tr>
                                                            <td class="fw-semibold">
                                                                {{ $course->classroom?->name ?? $course->title }}
                                                                @if($lagsBehind)
                                                                    <span class="badge bg-warning-lt ms-1" title="Jumlah materi/tugas lebih sedikit dibanding kelas paralel lain">Belum Lengkap</span>
                                                                @endif
                                                                <div class="text-muted small fw-normal">{{ $course->title }}</div>
                                                            </td>
                                                            <td class="small text-muted">{{ $course->students_count }} siswa</td>
                                                            <td class="small">{{ $course->published_materials_count }}</td>
                                                            <td class="small">{{ $course->assignments_count }}</td>
                                                            <td>
                                                                <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : 'bg-warning-lt' }} text-uppercase">{{ $course->status }}</span>
                                                            </td>
                                                            <td class="text-end">
                                                                <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-primary">Kelola Kelas</a>
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
                                <i class="ti ti-books-off fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada kelas digital yang Anda ampu pada Tahun Ajaran ini.
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="tab-pane d-none" id="courses-homeroom">
            <div class="alert alert-info py-2 small mb-3">
                <i class="ti ti-info-circle me-1"></i>
                Sebagai <strong>Wali Kelas</strong>, Anda dapat memantau seluruh mata pelajaran pada rombel perwalian Anda (akses pantau / <em>read-only</em>).
            </div>
            <div class="row row-cards">
                @foreach($homeroomCourses as $course)
                    @include('courses.partials.course-card', ['course' => $course, 'completedCountByCourse' => $completedCountByCourse, 'isHomeroomTab' => true])
                @endforeach
            </div>
        </div>
    </div>
@else
    @if(! auth()->user()->isSiswa() && ($filterSubjects->isNotEmpty() || $filterGrades->isNotEmpty()))
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-4 col-md-3">
                        <select class="form-select form-select-sm js-course-filter-subject" aria-label="Filter Mata Pelajaran">
                            <option value="">Semua Mata Pelajaran</option>
                            @foreach($filterSubjects as $subj)
                                <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-3 col-md-2">
                        <select class="form-select form-select-sm js-course-filter-grade" aria-label="Filter Tingkat Kelas">
                            <option value="">Semua Tingkat</option>
                            @foreach($filterGrades as $gr)
                                <option value="{{ $gr }}">Kelas {{ $gr }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->user()->isSiswa())
        <div class="row row-cards">
            @forelse($taughtCourses as $course)
                @include('courses.partials.course-card', ['course' => $course, 'completedCountByCourse' => $completedCountByCourse, 'isHomeroomTab' => false])
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="ti ti-books-off fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada kelas digital.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    @else
        <div class="vstack gap-4">
            @forelse($taughtGradeGroups as $gradeGrp)
                <div class="js-grade-section" data-grade="{{ $gradeGrp['grade_level'] ?? '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                            {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Lainnya' }}
                        </span>
                        <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_courses'] }} Rombel</span>
                    </div>
                    <div class="vstack gap-3">
                        @foreach($gradeGrp['subjects'] as $grp)
                            <div class="card js-course-group" data-subject-id="{{ $grp['subject_id'] }}" data-grade="{{ $grp['grade_level'] ?? '' }}">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h3 class="card-title mb-0">{{ $grp['subject_name'] }}</h3>
                                        <span class="badge bg-blue-lt">{{ $grp['courses']->count() }} Rombel</span>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="ti ti-users me-1"></i>{{ $grp['students_count'] }} Siswa
                                        &middot; Maks {{ $grp['max_materials'] }} Materi
                                        &middot; Maks {{ $grp['max_assignments'] }} Tugas
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table lms-table table-vcenter mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kelas</th>
                                                <th>Siswa</th>
                                                <th>Materi Terbit</th>
                                                <th>Tugas</th>
                                                <th>Status</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($grp['courses'] as $course)
                                                @php
                                                    $lagsBehind = $grp['courses']->count() > 1 && (
                                                        $course->published_materials_count < $grp['max_materials']
                                                        || $course->assignments_count < $grp['max_assignments']
                                                    );
                                                @endphp
                                                <tr>
                                                    <td class="fw-semibold">
                                                        {{ $course->classroom?->name ?? $course->title }}
                                                        @if($lagsBehind)
                                                            <span class="badge bg-warning-lt ms-1" title="Jumlah materi/tugas lebih sedikit dibanding kelas paralel lain">Belum Lengkap</span>
                                                        @endif
                                                        <div class="text-muted small fw-normal">{{ $course->title }}</div>
                                                    </td>
                                                    <td class="small text-muted">{{ $course->students_count }} siswa</td>
                                                    <td class="small">{{ $course->published_materials_count }}</td>
                                                    <td class="small">{{ $course->assignments_count }}</td>
                                                    <td>
                                                        <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : 'bg-warning-lt' }} text-uppercase">{{ $course->status }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-outline-primary">Kelola Kelas</a>
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
                        <i class="ti ti-books-off fs-1 d-block mb-2 opacity-50"></i>
                        Belum ada kelas digital.
                    </div>
                </div>
            @endforelse
        </div>
    @endif
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.js-courses-tab');
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

    const subjectSelect = document.querySelector('.js-course-filter-subject');
    const gradeSelect = document.querySelector('.js-course-filter-grade');
    const groups = document.querySelectorAll('.js-course-group');
    const gradeSections = document.querySelectorAll('.js-grade-section');

    const applyFilters = () => {
        const subjectVal = subjectSelect?.value || '';
        const gradeVal = gradeSelect?.value || '';
        groups.forEach((el) => {
            const matchSubject = !subjectVal || el.dataset.subjectId === subjectVal;
            const matchGrade = !gradeVal || el.dataset.grade === gradeVal;
            el.classList.toggle('d-none', !(matchSubject && matchGrade));
        });
        gradeSections.forEach((sec) => {
            const visibleCards = sec.querySelectorAll('.js-course-group:not(.d-none)').length;
            sec.classList.toggle('d-none', visibleCards === 0);
        });
    };

    subjectSelect?.addEventListener('change', applyFilters);
    gradeSelect?.addEventListener('change', applyFilters);
});
</script>
@endpush
