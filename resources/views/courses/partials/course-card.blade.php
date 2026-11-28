@php
    $isStudent = auth()->user()->isSiswa();
    $doneCount = $isStudent ? (int) ($completedCountByCourse[$course->id] ?? 0) : 0;
    $totalPublished = (int) ($course->published_materials_count ?? $course->materials_count);
    $progressPct = ($isStudent && $totalPublished > 0) ? (int) round(($doneCount / $totalPublished) * 100) : 0;
    $homeroomTeacher = $course->classroom?->userAssignments->first()?->user;
@endphp
<div class="col-md-6 col-lg-4">
    <div class="card h-100 overflow-hidden">
        <div style="height: 6px; background: var(--lms-gradient);"></div>
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : ($course->status === 'draft' ? 'bg-warning-lt' : 'bg-secondary-lt') }} text-uppercase">
                    {{ $course->status }}
                </span>
                <span class="badge bg-blue-lt">{{ $course->classroom?->name ?? 'Umum' }}</span>
            </div>

            <h3 class="card-title mb-1">{{ $course->title }}</h3>
            <div class="text-muted small mb-2">
                {{ $course->subject?->name ?? 'Mata Pelajaran' }}
                @if($course->academicYear)
                    &middot; {{ $course->academicYear->name }}
                @endif
            </div>

            <div class="d-flex flex-column gap-1 text-muted small mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-user-check text-primary"></i>
                    <span>Pengajar: {{ $course->teachers->pluck('name')->join(', ') ?: 'Belum ada pengajar' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-shield-check text-success"></i>
                    <span>Wali Kelas: {{ $homeroomTeacher?->name ?? '-' }}</span>
                </div>
            </div>

            @if($isStudent)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Progres Materi</span>
                        <span class="fw-medium">{{ $doneCount }}/{{ $totalPublished }} ({{ $progressPct }}%)</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-3 text-muted small mb-3 pt-2 border-top">
                <span><i class="ti ti-users me-1"></i>{{ $course->students_count }} Siswa</span>
                <span><i class="ti ti-book me-1"></i>{{ $course->materials_count }} Materi</span>
                <span><i class="ti ti-checklist me-1"></i>{{ $course->assignments_count }} Tugas</span>
            </div>

            <a href="{{ route('courses.show', $course) }}" class="btn {{ !empty($isHomeroomTab) ? 'btn-outline-primary' : 'btn-primary' }} w-100 mt-auto">
                <i class="ti {{ !empty($isHomeroomTab) ? 'ti-eye' : 'ti-arrow-right' }} me-1"></i>
                {{ !empty($isHomeroomTab) ? 'Pantau Kelas' : 'Buka Kelas' }}
            </a>
        </div>
    </div>
</div>
