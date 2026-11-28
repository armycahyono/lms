@extends('layouts.app')

@section('title', 'Kuis & Ujian')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Evaluasi &amp; Asesmen Berbasis Soal</div>
            <h2 class="page-title">Kuis &amp; Ujian</h2>
        </div>
        @if($canManageAny && $courses->isNotEmpty())
            <div class="col-auto d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-batch-quiz">
                    <i class="ti ti-file-plus me-1"></i> Buat Ujian Kelas (UH / Kuis / Remidi)
                </button>
                <a href="{{ route('exams.create') }}" class="btn btn-primary">
                    <i class="ti ti-device-desktop-plus me-1"></i> Buat Ujian Sekolah (CBT)
                </a>
            </div>
        @endif
    </div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="overflow-auto">
        <div class="lms-pill-tabs" role="tablist" aria-label="Kategori Kuis dan Ujian">
            <button type="button" class="lms-pill-tab {{ $activeTab === 'quizzes' ? 'active' : '' }} js-assessment-tab" data-target="tab-quizzes" role="tab" aria-selected="{{ $activeTab === 'quizzes' ? 'true' : 'false' }}">
                <i class="ti ti-file-check"></i>
                <span>Ujian Kelas di LMS (UH / Kuis / Remidi)</span>
                <span class="badge bg-blue-lt rounded-pill">{{ auth()->user()->isSiswa() ? $quizzes->count() : $quizPackageGroups->count() }}</span>
            </button>
            <button type="button" class="lms-pill-tab {{ $activeTab === 'cbt' ? 'active' : '' }} js-assessment-tab" data-target="tab-cbt" role="tab" aria-selected="{{ $activeTab === 'cbt' ? 'true' : 'false' }}">
                <i class="ti ti-device-desktop-check"></i>
                <span>Ujian Sekolah (CBT)</span>
                <span class="badge bg-blue-lt rounded-pill">{{ auth()->user()->isSiswa() ? $exams->count() : $examPackageGroups->count() }}</span>
            </button>
        </div>
    </div>
    <div class="btn-group flex-shrink-0" role="group" aria-label="Mode tampilan kuis dan ujian">
        <button type="button" class="btn btn-icon btn-outline-secondary active js-view-mode" data-mode="card" title="Tampilan Kartu" aria-label="Tampilan Kartu" aria-pressed="true">
            <i class="ti ti-layout-grid"></i>
        </button>
        <button type="button" class="btn btn-icon btn-outline-secondary js-view-mode" data-mode="list" title="Tampilan List" aria-label="Tampilan List" aria-pressed="false">
            <i class="ti ti-list"></i>
        </button>
    </div>
</div>

@if(! auth()->user()->isSiswa() && ($filterSubjects->isNotEmpty() || $filterGrades->isNotEmpty()))
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-4 col-md-3">
                    <select class="form-select form-select-sm js-filter-subject" aria-label="Filter Mata Pelajaran">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach($filterSubjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <select class="form-select form-select-sm js-filter-grade" aria-label="Filter Tingkat Kelas">
                        <option value="">Semua Tingkat</option>
                        @foreach($filterGrades as $gr)
                            <option value="{{ $gr }}">Kelas {{ $gr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-5 col-md-4">
                    <input type="search" class="form-control form-control-sm js-filter-search" placeholder="Cari judul kuis atau ujian..." aria-label="Cari judul">
                </div>
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-sm btn-link text-muted js-filter-reset">Reset Filter</button>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="tab-content">
    {{-- Tab 1: Ujian Kelas di LMS (UH, Kuis, Remedial) --}}
    <div class="tab-pane {{ $activeTab === 'quizzes' ? 'active' : 'd-none' }}" id="tab-quizzes">
        <div class="alert alert-info py-2 small mb-3">
            <strong>Ujian Kelas di LMS:</strong> Dikerjakan siswa langsung di LMS (mendukung <em>auto-save</em>, timer otomatis, koreksi PG/Isian multi-kunci, dan koreksi Esai per nomor).
        </div>

        <div class="row row-cards js-view-card">
            @if(auth()->user()->isSiswa())
                @forelse($quizzes as $quiz)
                    @php
                        $myAttempt = $quiz->attempts->first();
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $quiz->course?->subject?->name ?? 'Mapel' }}</span>
                                        <span class="badge {{ $quiz->quiz_type === 'remedial' ? 'bg-warning-lt' : ($quiz->quiz_type === 'pengayaan' ? 'bg-success-lt' : 'bg-secondary-lt') }} text-uppercase">
                                            {{ $quiz->quiz_type ?? 'reguler' }}
                                        </span>
                                        @if($quiz->target_remedial_only)
                                            <span class="badge bg-purple-lt">Khusus &lt; KKTP</span>
                                        @endif
                                    </div>
                                    @if($myAttempt && $myAttempt->submitted_at)
                                        <span class="badge bg-success-lt">Nilai: {{ $myAttempt->score ?? '-' }}</span>
                                    @endif
                                </div>

                                <h3 class="card-title mb-1">{{ $quiz->title }}</h3>
                                <div class="text-muted small mb-2">
                                    {{ $quiz->course?->title }} &middot; {{ $quiz->course?->classroom?->name }}
                                </div>
                                <div class="text-muted small mb-3">
                                    <span><i class="ti ti-list-numbers me-1"></i>{{ $quiz->questions_count }} Soal</span>
                                    &middot; <span><i class="ti ti-clock me-1"></i>{{ $quiz->duration_minutes }} Menit</span>
                                    &middot; <span>KKM: {{ $quiz->passing_score ?? 75 }}</span>
                                </div>

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">{{ $myAttempt?->status === 'in_progress' ? 'Sedang dikerjakan' : ($myAttempt?->submitted_at ? 'Selesai' : 'Belum dimulai') }}</span>
                                    <a href="{{ route('courses.quizzes.show', [$quiz->course_id, $quiz]) }}" class="btn btn-sm btn-primary">
                                        {{ $myAttempt?->submitted_at ? 'Lihat Hasil' : 'Kerjakan' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-file-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada kuis atau ulangan harian kelas.
                            </div>
                        </div>
                    </div>
                @endforelse
            @else
                @forelse($quizPackageGroups as $pkg)
                    @php
                        $firstQuiz = $pkg['first_quiz'];
                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                    @endphp
                    <div class="col-md-6 col-lg-4 js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $pkg['subject_name'] }}@if($pkg['grade_level']) &middot; Kelas {{ $pkg['grade_level'] }}@endif</span>
                                        <span class="badge {{ $pkg['quiz_type'] === 'remedial' ? 'bg-warning-lt' : ($pkg['quiz_type'] === 'pengayaan' ? 'bg-success-lt' : 'bg-secondary-lt') }} text-uppercase">
                                            {{ $pkg['quiz_type'] }}
                                        </span>
                                        @if($pkg['target_remedial_only'])
                                            <span class="badge bg-purple-lt">Khusus &lt; KKTP</span>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}" title="Cakupan kelas pada mapel &amp; tingkat ini">
                                        {{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas
                                    </span>
                                </div>

                                <h3 class="card-title mb-1">{{ $pkg['title'] }}</h3>
                                <div class="text-muted small mb-2">
                                    <span><i class="ti ti-list-numbers me-1"></i>{{ $pkg['questions_count'] }} Soal</span>
                                    &middot; <span><i class="ti ti-clock me-1"></i>{{ $pkg['duration_minutes'] }} Menit</span>
                                    &middot; <span>KKM: {{ $pkg['passing_score'] }}</span>
                                </div>

                                <div class="mb-2">
                                    <div class="text-muted small mb-1">Kelas Penerima:</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($pkg['quizzes'] as $qz)
                                            <a href="{{ route('courses.quizzes.show', [$qz->course_id, $qz]) }}" class="badge bg-azure-lt text-decoration-none">
                                                {{ $qz->course?->classroom?->name ?? $qz->course?->title }}
                                                @if(($qz->pending_essay_count ?? 0) > 0)
                                                    <span class="text-warning ms-1">({{ $qz->pending_essay_count }})</span>
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
                                        {{ $pkg['total_attempts'] }} pengerjaan
                                        @if($pkg['pending_essay_count'] > 0)
                                            &middot; <span class="text-warning fw-semibold">{{ $pkg['pending_essay_count'] }} koreksi</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('courses.quizzes.show', [$firstQuiz->course_id, $firstQuiz]) }}" class="btn btn-sm btn-outline-primary">
                                        Kelola Soal &amp; Koreksi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-file-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada kuis atau ulangan harian kelas.
                            </div>
                        </div>
                    </div>
                @endforelse
            @endif
        </div>

        @if(auth()->user()->isSiswa())
            <div class="card js-view-list d-none">
                <div class="table-responsive">
                    <table class="table lms-table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Judul Kuis / Ujian</th>
                                <th>Mapel &amp; Kelas</th>
                                <th>Soal / Durasi</th>
                                <th>Status / Nilai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quizzes as $quiz)
                                @php $myAttempt = $quiz->attempts->first(); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $quiz->title }}</td>
                                    <td class="small text-muted">{{ $quiz->course?->subject?->name ?? 'Mapel' }} &middot; {{ $quiz->course?->classroom?->name }}</td>
                                    <td class="small text-muted">{{ $quiz->questions_count }} soal &middot; {{ $quiz->duration_minutes }} mnt</td>
                                    <td>
                                        @if($myAttempt && $myAttempt->submitted_at)
                                            <span class="badge bg-success-lt">Nilai: {{ $myAttempt->score ?? '-' }}</span>
                                        @elseif($myAttempt?->status === 'in_progress')
                                            <span class="badge bg-warning-lt">Sedang dikerjakan</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Belum dimulai</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('courses.quizzes.show', [$quiz->course_id, $quiz]) }}" class="btn btn-sm btn-primary">
                                            {{ $myAttempt?->submitted_at ? 'Lihat Hasil' : 'Kerjakan' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kuis atau ulangan harian kelas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="vstack gap-4 js-view-list d-none">
                @forelse($quizGradeGroups as $gradeGrp)
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
                                            <span class="badge bg-blue-lt">{{ $sgGroup['packages']->count() }} Paket</span>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table lms-table table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Judul Paket</th>
                                                    <th>Tipe</th>
                                                    <th>Kelas Penerima &amp; Cakupan</th>
                                                    <th>Pengerjaan</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sgGroup['packages'] as $pkg)
                                                    @php
                                                        $firstQuiz = $pkg['first_quiz'];
                                                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                                                    @endphp
                                                    <tr class="js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                                                        <td>
                                                            <div class="fw-semibold">{{ $pkg['title'] }}</div>
                                                            <div class="text-muted small">{{ $pkg['questions_count'] }} soal &middot; {{ $pkg['duration_minutes'] }} mnt &middot; KKM {{ $pkg['passing_score'] }}</div>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $pkg['quiz_type'] === 'remedial' ? 'bg-warning-lt' : ($pkg['quiz_type'] === 'pengayaan' ? 'bg-success-lt' : 'bg-secondary-lt') }} text-uppercase">
                                                                {{ $pkg['quiz_type'] }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}">{{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas</span>
                                                                @foreach($pkg['quizzes'] as $qz)
                                                                    <a href="{{ route('courses.quizzes.show', [$qz->course_id, $qz]) }}" class="badge bg-azure-lt text-decoration-none">
                                                                        {{ $qz->course?->classroom?->name ?? $qz->course?->title }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                            @if($pkg['missing_courses']->isNotEmpty())
                                                                <div class="text-warning small mt-1">Belum: {{ $pkg['missing_courses']->map(fn ($c) => $c->classroom?->name ?? $c->title)->implode(', ') }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="small">
                                                            {{ $pkg['total_attempts'] }} pengerjaan
                                                            @if($pkg['pending_essay_count'] > 0)
                                                                <div class="text-warning fw-semibold">{{ $pkg['pending_essay_count'] }} koreksi</div>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="{{ route('courses.quizzes.show', [$firstQuiz->course_id, $firstQuiz]) }}" class="btn btn-sm btn-outline-primary">Kelola</a>
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
                        <div class="card-body text-center text-muted py-4">Belum ada kuis atau ulangan harian kelas.</div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    {{-- Tab 2: Ujian Sekolah (CBT) --}}
    <div class="tab-pane {{ $activeTab === 'cbt' ? 'active' : 'd-none' }}" id="tab-cbt">
        <div class="alert alert-azure py-2 small mb-3">
            <strong>Ujian Sekolah (CBT):</strong> Paket ujian berbasis Mata Pelajaran &amp; Tingkat. Susun soal dari Bank Soal LMS &rarr; <strong>Kirim Paket ke CBTPGRI</strong> &rarr; siswa mengerjakan di aplikasi CBTPGRI &rarr; <strong>Tarik Nilai</strong> kembali ke LMS.
        </div>

        @if(auth()->user()->isSiswa())
            <div class="row row-cards js-view-card">
                @forelse($exams as $exam)
                    @php
                        $myResult = $exam->results->first();
                        $targetUrl = $exam->launch_url ?: $cbtBaseUrl;
                        $scheduleStatus = $exam->scheduleStatus($myResult);
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $exam->exam_type }}</span>
                                        <span class="badge {{ $scheduleStatus['badge'] }}">{{ $scheduleStatus['label'] }}</span>
                                    </div>
                                    <span class="text-muted small">{{ $exam->course?->classroom?->name }}</span>
                                </div>
                                <h3 class="card-title mb-1">{{ $exam->title }}</h3>
                                <div class="text-muted small mb-2">{{ $exam->course?->subject?->name ?? $exam->course?->title }}</div>

                                <div class="p-2 rounded bg-light border small mb-3">
                                    @if($exam->start_at)
                                        <div>
                                            <i class="ti ti-calendar-event me-1 text-primary"></i>
                                            <strong>{{ $exam->start_at->translatedFormat('l, d M Y') }}</strong>
                                        </div>
                                        <div class="text-muted">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ $exam->start_at->format('H:i') }}@if($exam->end_at) &ndash; {{ $exam->end_at->format('H:i') }}@endif WIB
                                            &middot; {{ $exam->duration_minutes }} Menit
                                        </div>
                                        @if($exam->session_name || $exam->room_name)
                                            <div class="text-muted mt-1">
                                                <i class="ti ti-building me-1"></i>
                                                {{ collect([$exam->session_name, $exam->room_name])->filter()->implode(' · ') }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted">
                                            <i class="ti ti-clock-pause me-1"></i>Menunggu rilis jadwal dari CBTPGRI &middot; {{ $exam->duration_minutes }} Menit
                                        </div>
                                    @endif
                                </div>

                                @if($myResult)
                                    @if($myResult->isPendingEssay())
                                        <div class="alert alert-warning py-2 small mb-3">
                                            Status: <strong>Menunggu Koreksi Esai</strong>
                                            @if($myResult->auto_score !== null)
                                                (Skor PG Sementara: <strong>{{ $myResult->auto_score }}</strong>)
                                            @endif
                                        </div>
                                    @else
                                        <div class="alert alert-success py-2 small mb-3">
                                            Nilai Ujian: <strong>{{ $myResult->score }}</strong>
                                        </div>
                                    @endif
                                @endif

                                @if($scheduleStatus['can_launch'])
                                    <a href="{{ $targetUrl }}" target="_blank" rel="noopener" class="btn btn-primary w-100 mt-auto">
                                        <i class="ti ti-external-link me-1"></i> Masuk ke Ujian (CBTPGRI)
                                    </a>
                                @elseif(! $myResult)
                                    <button type="button" class="btn btn-outline-secondary w-100 mt-auto" disabled>
                                        <i class="ti ti-lock me-1"></i> {{ $scheduleStatus['label'] }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-device-desktop-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada jadwal ujian sekolah (CBT).
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="card js-view-list d-none">
                <div class="table-responsive">
                    <table class="table lms-table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Judul Ujian</th>
                                <th>Mapel &amp; Jenis</th>
                                <th>Jadwal Pelaksanaan</th>
                                <th>Durasi</th>
                                <th>Status / Nilai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exams as $exam)
                                @php
                                    $myResult = $exam->results->first();
                                    $targetUrl = $exam->launch_url ?: $cbtBaseUrl;
                                    $scheduleStatus = $exam->scheduleStatus($myResult);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $exam->title }}</div>
                                        @if($exam->session_name || $exam->room_name)
                                            <div class="text-muted small">{{ collect([$exam->session_name, $exam->room_name])->filter()->implode(' · ') }}</div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-blue-lt me-1">{{ $exam->exam_type }}</span>
                                        {{ $exam->course?->subject?->name ?? $exam->course?->title }}
                                    </td>
                                    <td class="small">
                                        @if($exam->start_at)
                                            <div class="fw-medium">{{ $exam->start_at->translatedFormat('d M Y') }}</div>
                                            <div class="text-muted">{{ $exam->start_at->format('H:i') }}@if($exam->end_at) &ndash; {{ $exam->end_at->format('H:i') }}@endif WIB</div>
                                        @else
                                            <span class="text-muted">Menunggu rilis CBTPGRI</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $exam->duration_minutes }} mnt</td>
                                    <td>
                                        @if($myResult)
                                            @if($myResult->isPendingEssay())
                                                <span class="badge bg-warning-lt">Menunggu Koreksi Esai @if($myResult->auto_score !== null)(PG: {{ $myResult->auto_score }})@endif</span>
                                            @else
                                                <span class="badge bg-success-lt">Nilai: {{ $myResult->score }}</span>
                                            @endif
                                        @else
                                            <span class="badge {{ $scheduleStatus['badge'] }}">{{ $scheduleStatus['label'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($scheduleStatus['can_launch'])
                                            <a href="{{ $targetUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                                                <i class="ti ti-external-link me-1"></i> Masuk CBT
                                            </a>
                                        @elseif(! $myResult)
                                            <span class="text-muted small">{{ $scheduleStatus['label'] }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada jadwal ujian sekolah (CBT).</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="row row-cards js-view-card">
                @forelse($examPackageGroups as $pkg)
                    @php
                        $firstExam = $pkg['first_exam'];
                        $tc = $pkg['type_counts'];
                        $isAllSynced = $pkg['synced_count'] === $pkg['exams']->count();
                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                    @endphp
                    <div class="col-md-6 js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-blue-lt">{{ $pkg['exam_type'] }}</span>
                                        <span class="badge bg-indigo-lt">{{ $pkg['subject_name'] }}@if($pkg['grade_level']) &middot; Kelas {{ $pkg['grade_level'] }}@endif</span>
                                        <span class="badge {{ $isAllSynced ? 'bg-success-lt' : 'bg-warning-lt' }}">
                                            {{ $isAllSynced ? 'Tersinkron CBTPGRI' : 'Tersinkron '.$pkg['synced_count'].'/'.$pkg['exams']->count().' Kelas' }}
                                        </span>
                                        <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}">
                                            {{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas
                                        </span>
                                    </div>
                                    <span class="badge bg-secondary-lt">{{ $pkg['total_students'] }} Siswa</span>
                                </div>

                                <h3 class="card-title mb-2">{{ $pkg['title'] }}</h3>

                                <div class="mb-2">
                                    <div class="text-muted small mb-1">Kelas Peserta:</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($pkg['exams'] as $ex)
                                            <a href="{{ route('exams.show', $ex) }}" class="badge bg-azure-lt text-decoration-none">
                                                {{ $ex->course?->classroom?->name ?? $ex->course?->title }}
                                                @if($ex->last_synced_at) <i class="ti ti-check ms-1"></i> @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                @if($pkg['missing_courses']->isNotEmpty())
                                    <div class="text-warning small mb-2">
                                        <i class="ti ti-alert-triangle me-1"></i>Belum ada di kelas: {{ $pkg['missing_courses']->map(fn ($c) => $c->classroom?->name ?? $c->title)->implode(', ') }}
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">
                                        Komposisi Soal ({{ $pkg['total_questions'] }} di paket @if($pkg['display_question_count']) &middot; Tampil {{ $pkg['display_question_count'] }} acak @endif):
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-light text-dark border">PG: {{ $tc['multiple_choice'] }}</span>
                                        <span class="badge bg-light text-dark border">PGK: {{ $tc['multiple_choice_complex'] }}</span>
                                        <span class="badge bg-light text-dark border">Jodohkan: {{ $tc['matching'] }}</span>
                                        <span class="badge bg-light text-dark border">Isian: {{ $tc['short_answer'] }}</span>
                                        <span class="badge bg-light text-dark border">Esai: {{ $tc['essay'] }}</span>
                                    </div>
                                </div>

                                <div class="text-muted small mb-3">
                                    <div><i class="ti ti-calendar-plus me-1"></i>Dibuat: {{ $pkg['created_at']?->format('d M Y H:i') ?? '-' }}</div>
                                    <div><i class="ti ti-edit me-1"></i>Diubah: {{ $pkg['updated_at']?->format('d M Y H:i') ?? '-' }}</div>
                                </div>

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center gap-2">
                                    <span class="text-muted small">Nilai masuk: <strong>{{ $pkg['total_results'] }}</strong> / {{ $pkg['total_students'] }} siswa</span>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('exams.edit', $firstExam) }}" class="btn btn-sm btn-outline-secondary" title="Edit Pengaturan &amp; Kelas Paket">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="{{ route('exams.show', $firstExam) }}" class="btn btn-sm btn-primary">
                                            Kelola Paket Soal &amp; Nilai
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="ti ti-device-desktop-check fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada paket Ujian Sekolah (CBT).
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="vstack gap-4 js-view-list d-none">
                @forelse($examGradeGroups as $gradeGrp)
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
                                            <span class="badge bg-blue-lt">{{ $sgGroup['packages']->count() }} Paket Ujian</span>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table lms-table table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Judul Paket Ujian</th>
                                                    <th>Kelas Peserta &amp; Cakupan</th>
                                                    <th>Soal</th>
                                                    <th>Sinkron &amp; Nilai</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sgGroup['packages'] as $pkg)
                                                    @php
                                                        $firstExam = $pkg['first_exam'];
                                                        $isAllSynced = $pkg['synced_count'] === $pkg['exams']->count();
                                                        $isComplete = $pkg['covered_count'] >= $pkg['expected_count'];
                                                    @endphp
                                                    <tr class="js-filterable-item" data-subject-id="{{ $pkg['subject_id'] }}" data-grade="{{ $pkg['grade_level'] ?? '' }}" data-title="{{ mb_strtolower($pkg['title']) }}">
                                                        <td>
                                                            <span class="badge bg-blue-lt me-1">{{ $pkg['exam_type'] }}</span>
                                                            <span class="fw-semibold">{{ $pkg['title'] }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                <span class="badge {{ $isComplete ? 'bg-success-lt' : 'bg-warning-lt' }}">{{ $pkg['covered_count'] }}/{{ $pkg['expected_count'] }} Kelas</span>
                                                                @foreach($pkg['exams'] as $ex)
                                                                    <a href="{{ route('exams.show', $ex) }}" class="badge bg-azure-lt text-decoration-none">
                                                                        {{ $ex->course?->classroom?->name ?? $ex->course?->title }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                            @if($pkg['missing_courses']->isNotEmpty())
                                                                <div class="text-warning small mt-1">Belum: {{ $pkg['missing_courses']->map(fn ($c) => $c->classroom?->name ?? $c->title)->implode(', ') }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="small text-muted">{{ $pkg['total_questions'] }} soal</td>
                                                        <td class="small">
                                                            <span class="badge {{ $isAllSynced ? 'bg-success-lt' : 'bg-warning-lt' }}">{{ $pkg['synced_count'] }}/{{ $pkg['exams']->count() }} sinkron</span>
                                                            <div class="text-muted mt-1">{{ $pkg['total_results'] }}/{{ $pkg['total_students'] }} nilai</div>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="d-inline-flex gap-1">
                                                                <a href="{{ route('exams.edit', $firstExam) }}" class="btn btn-sm btn-outline-secondary" title="Edit Paket">
                                                                    <i class="ti ti-edit"></i>
                                                                </a>
                                                                <a href="{{ route('exams.show', $firstExam) }}" class="btn btn-sm btn-primary">Kelola</a>
                                                            </div>
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
                        <div class="card-body text-center text-muted py-4">Belum ada paket Ujian Sekolah (CBT).</div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>

@if($canManageAny && $courses->isNotEmpty())
    {{-- Modal Buat Ujian Kelas (Kuis / UH / Remedial) --}}
    <div class="modal modal-blur fade" id="modal-batch-quiz" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('quizzes.store-batch') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Ujian Kelas (UH / Kuis / Remedial)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Pilih Kelas Digital Tujuan</label>
                            <div class="border rounded p-2 d-flex flex-wrap gap-3" style="max-height: 130px; overflow-y: auto;">
                                @foreach($courses as $mc)
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="course_ids[]" value="{{ $mc->id }}" class="form-check-input">
                                        <span class="form-check-label small fw-medium">{{ $mc->title }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-hint">Jika memilih 1 kelas, Anda langsung diarahkan ke halaman tambah/pilih soal dari Bank Soal.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label">Judul Ujian Kelas / Kuis</label>
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
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Tipe</label>
                                <select name="quiz_type" class="form-select">
                                    <option value="reguler">Reguler</option>
                                    <option value="remedial">Remedial</option>
                                    <option value="pengayaan">Pengayaan</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">KKM</label>
                                <input type="number" step="1" name="passing_score" value="75" class="form-control">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Durasi (Menit)</label>
                                <input type="number" name="duration_minutes" value="30" class="form-control" min="1" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Maks Percobaan</label>
                                <input type="number" name="max_attempts" value="1" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label">Kuis Induk (Opsional, untuk Saring Remedial &lt; KKM)</label>
                                <select name="parent_quiz_id" class="form-select">
                                    <option value="">-- Tidak Mengacu Kuis Induk --</option>
                                    @foreach($quizzes as $pq)
                                        <option value="{{ $pq->id }}">{{ $pq->title }} ({{ $pq->course?->classroom?->name }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 pt-md-4">
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="target_remedial_only" value="1" class="form-check-input">
                                    <span class="form-check-label small">Kunci hanya untuk siswa nilai &lt; KKM</span>
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
                        <button type="submit" class="btn btn-primary">Simpan &amp; Lanjut Pilih Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Buat Jadwal Ujian CBTPGRI (Bisa Banyak Kelas Paralel) --}}
    <div class="modal modal-blur fade" id="modal-add-exam" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('exams.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Jadwal Ujian Resmi (CBTPGRI)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Pilih Kelas Digital Tujuan (Bisa Lebih dari 1 Kelas Paralel)</label>
                            <div class="border rounded p-2 d-flex flex-wrap gap-3" style="max-height: 130px; overflow-y: auto;">
                                @foreach($courses as $course)
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" class="form-check-input">
                                        <span class="form-check-label small fw-medium">{{ $course->title }} ({{ $course->classroom?->name }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Jenis</label>
                                <select name="exam_type" class="form-select">
                                    <option value="PTS">PTS</option>
                                    <option value="PAS">PAS</option>
                                    <option value="US">US</option>
                                    <option value="Simulasi">Simulasi</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Ref Durasi</label>
                                <div class="input-group">
                                    <input type="number" name="duration_minutes" value="60" min="1" class="form-control" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Judul Ujian</label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: PAS Bahasa Indonesia X" required>
                        </div>
                        <div>
                            <label class="form-label">Kode Ujian CBTPGRI (Opsional)</label>
                            <input type="text" name="cbt_exam_code" class="form-control" placeholder="Otomatis jika dikosongkan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Jadwal Ujian</button>
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
    const tabs = document.querySelectorAll('.js-assessment-tab');
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

    const viewButtons = document.querySelectorAll('.js-view-mode');
    const cardViews = document.querySelectorAll('.js-view-card');
    const listViews = document.querySelectorAll('.js-view-list');
    const storageKey = 'lms_exams_view_mode';

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

    const defaultMode = @json(auth()->user()->isSiswa() ? 'card' : 'list');
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
    const resetBtn = document.querySelector('.js-filter-reset');
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
    resetBtn?.addEventListener('click', () => {
        if (subjectSelect) subjectSelect.value = '';
        if (gradeSelect) gradeSelect.value = '';
        if (searchInput) searchInput.value = '';
        applyFilters();
    });
});
</script>
@endpush
