@extends('layouts.app')

@section('title', 'Data Guru & Siswa')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Direktori Sekolah' }}
            </div>
            <h2 class="page-title">Data Guru &amp; Siswa</h2>
        </div>
    </div>
</div>

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Kategori Direktori">
        <a href="{{ route('directory.index', ['tab' => 'teachers']) }}" class="lms-pill-tab {{ $tab !== 'students' ? 'active' : '' }}">
            <i class="ti ti-chalkboard"></i>
            <span>Data Guru</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $teachers->count() }}</span>
        </a>
        <a href="{{ route('directory.index', ['tab' => 'students']) }}" class="lms-pill-tab {{ $tab === 'students' ? 'active' : '' }}">
            <i class="ti ti-school"></i>
            <span>Data Siswa</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $students->count() }}</span>
        </a>
    </div>
</div>

@if($tab !== 'students')
    <form method="GET" action="{{ route('directory.index') }}" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="tab" value="teachers">
        <div class="input-icon flex-grow-1" style="max-width: 360px;">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama atau username guru...">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <div class="d-flex flex-column gap-2">
        @forelse($teachers as $teacher)
            @php
                $positions = $teacher->assignments->map(function ($a): string {
                    $label = str_replace('_', ' ', ucfirst((string) $a->role_type));
                    if ($a->classroom) {
                        $label .= ' (' . $a->classroom->name . ')';
                    }

                    return $label;
                })->unique()->values();
            @endphp
            <div class="lms-row-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="avatar rounded-circle bg-blue-lt text-primary fw-bold flex-shrink-0">
                        {{ strtoupper(substr($teacher->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold text-dark">{{ $teacher->name }}</span>
                            <span class="badge bg-blue-lt text-uppercase">{{ $teacher->role }}</span>
                        </div>
                        <div class="text-muted small">
                            <code>{{ $teacher->profile?->nip_nisn ?: $teacher->username }}</code>
                            @if($teacher->email) &middot; {{ $teacher->email }} @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @forelse($positions as $pos)
                        <span class="lms-metric-chip bg-purple-lt text-purple">
                            <i class="ti ti-briefcase"></i> {{ $pos }}
                        </span>
                    @empty
                        <span class="lms-metric-chip">Guru</span>
                    @endforelse

                    @if($teacher->profile?->phone)
                        <a href="tel:{{ $teacher->profile->phone }}" class="lms-metric-chip text-decoration-none">
                            <i class="ti ti-phone text-primary"></i> {{ $teacher->profile->phone }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data guru.</div></div>
        @endforelse
    </div>
@else
    <form method="GET" action="{{ route('directory.index') }}" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="students">
        <div class="col-sm-5">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama atau NISN siswa...">
            </div>
        </div>
        <div class="col-sm-4">
            <select name="classroom_id" class="form-select">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3 col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="d-flex flex-column gap-2">
        @forelse($students as $student)
            @php
                $phone = $student->profile?->phone;
                $waDigits = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
                if ($waDigits && str_starts_with($waDigits, '0')) {
                    $waDigits = '62' . substr($waDigits, 1);
                }

                $studentClassroom = $student->classrooms->first()
                    ?? $student->studentClassHistories->first()?->classroom;
                $waliKelasName = $studentClassroom?->userAssignments->first()?->user?->name ?? '-';
                $parentNames = $student->parents->pluck('name')->filter()->implode(', ');
                $parentPhones = $student->parents
                    ->map(fn ($parent) => $parent->profile?->phone)
                    ->filter()
                    ->unique()
                    ->values();
            @endphp
            <div class="lms-row-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="avatar rounded-circle bg-purple-lt text-purple fw-bold flex-shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="fw-bold text-dark text-truncate">{{ $student->name }}</div>
                        <div class="text-muted small">
                            <code>{{ $student->username }}</code>
                            @if($studentClassroom)
                                &middot; {{ $studentClassroom->name }}
                            @endif
                            @if($student->email)
                                &middot; {{ $student->email }}
                            @endif
                        </div>
                        <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                            <span>
                                <i class="ti ti-user-check text-primary me-1"></i>
                                Wali Kelas: <span class="text-dark">{{ $waliKelasName }}</span>
                            </span>
                            <span>
                                <i class="ti ti-users text-purple me-1"></i>
                                Orang Tua: <span class="text-dark">{{ $parentNames !== '' ? $parentNames : '-' }}</span>
                            </span>
                            <span>
                                <i class="ti ti-phone-call text-success me-1"></i>
                                Telp Ortu:
                                @if($parentPhones->isNotEmpty())
                                    @foreach($parentPhones as $idx => $parentPhone)
                                        @if($idx > 0), @endif
                                        <a href="tel:{{ $parentPhone }}" class="text-decoration-none text-dark">{{ $parentPhone }}</a>
                                    @endforeach
                                @else
                                    <span>-</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($phone)
                        <a href="tel:{{ $phone }}" class="lms-metric-chip text-decoration-none">
                            <i class="ti ti-phone text-primary"></i> {{ $phone }}
                        </a>
                        @if($waDigits)
                            <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="ti ti-brand-whatsapp me-1"></i> Hubungi WA
                            </a>
                        @endif
                    @else
                        <span class="lms-metric-chip text-muted">Belum ada no. telepon</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada data siswa.</div></div>
        @endforelse
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('form[action*="direktori"] input[name="q"], input[name="q"]');
    const listContainer = document.querySelector('.d-flex.flex-column.gap-2');
    const classroomSelect = document.querySelector('select[name="classroom_id"]');

    if (classroomSelect && classroomSelect.form) {
        classroomSelect.addEventListener('change', function () {
            this.form.submit();
        });
    }

    if (!searchInput || !listContainer) {
        return;
    }

    const cards = Array.from(listContainer.querySelectorAll('.lms-row-card'));
    if (cards.length === 0) {
        return;
    }

    let emptyEl = document.getElementById('directory-live-empty');
    if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.id = 'directory-live-empty';
        emptyEl.className = 'card d-none';
        emptyEl.innerHTML = '<div class="card-body text-center text-muted py-5">Tidak ada data yang cocok dengan pencarian "<strong id="directory-live-query"></strong>".</div>';
        listContainer.appendChild(emptyEl);
    }

    const emptyQueryEl = emptyEl.querySelector('#directory-live-query');

    function filterDirectory() {
        const keyword = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(function (card) {
            const text = (card.textContent || '').toLowerCase();
            const matched = keyword === '' || text.includes(keyword);
            card.classList.toggle('d-none', !matched);
            if (matched) {
                visibleCount++;
            }
        });

        if (visibleCount === 0 && keyword !== '') {
            if (emptyQueryEl) {
                emptyQueryEl.textContent = searchInput.value.trim();
            }
            emptyEl.classList.remove('d-none');
        } else {
            emptyEl.classList.add('d-none');
        }
    }

    searchInput.addEventListener('input', filterDirectory);
});
</script>
@endsection
