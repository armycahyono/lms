@extends('layouts.app')

@section('title', 'Kelas Digital')

@section('content')
<div class="page-header d-print-none mb-4">
    <h2 class="page-title">Daftar Kelas Digital</h2>
</div>

<div class="row row-cards">
    @forelse($courses as $course)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $course->status === 'active' ? 'bg-success-lt' : ($course->status === 'draft' ? 'bg-warning-lt' : 'bg-secondary-lt') }} text-uppercase">
                            {{ $course->status }}
                        </span>
                        <span class="text-muted small">{{ $course->classroom?->name }}</span>
                    </div>
                    <h3 class="card-title mb-1">{{ $course->title }}</h3>
                    <p class="text-muted small mb-2">{{ $course->subject?->name }} &middot; {{ $course->academicYear?->name }}</p>
                    <p class="text-muted small mb-3">Pengajar: {{ $course->teachers->pluck('name')->join(', ') ?: '-' }}</p>
                    <div class="d-flex gap-3 text-muted small mb-3">
                        <span><i class="ti ti-users me-1"></i>{{ $course->students_count }} Siswa</span>
                        <span><i class="ti ti-book me-1"></i>{{ $course->materials_count }} Materi</span>
                        <span><i class="ti ti-checklist me-1"></i>{{ $course->assignments_count }} Tugas</span>
                    </div>
                    <a href="{{ route('courses.show', $course) }}" class="btn btn-primary w-100">Buka Kelas</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body text-center text-muted py-5">Belum ada kelas digital.</div></div>
        </div>
    @endforelse
</div>
@endsection
