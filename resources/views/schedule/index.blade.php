@extends('layouts.app')

@section('title', 'Jadwal & Kalender')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Agenda Pembelajaran</div>
            <h2 class="page-title">Jadwal &amp; Kalender Akademik</h2>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-clock me-1 text-primary"></i> Jadwal Pembelajaran Mingguan</h3>
            </div>
            <div class="card-body">
                @php $hasAnySchedule = $schedulesByDay->isNotEmpty(); @endphp

                @if($hasAnySchedule)
                    <div class="d-flex flex-column gap-3">
                        @foreach($dayNames as $dayNum => $dayLabel)
                            @php $dayItems = $schedulesByDay->get($dayNum, collect()); @endphp
                            @if($dayItems->isNotEmpty())
                                <div>
                                    <div class="fw-bold small text-primary text-uppercase mb-2">{{ $dayLabel }}</div>
                                    <div class="list-group">
                                        @foreach($dayItems as $item)
                                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2 py-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $item->course?->subject?->name ?? $item->course?->title }}</div>
                                                    <div class="text-muted small">
                                                        {{ substr((string) $item->start_time, 0, 5) }} - {{ substr((string) $item->end_time, 0, 5) }}
                                                        @if($item->course?->classroom) &middot; {{ $item->course->classroom->name }} @endif
                                                        @if($item->room) &middot; Ruang {{ $item->room }} @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="{{ route('courses.show', $item->course_id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                                                    @if($canManageSchedules)
                                                        <form action="{{ route('schedules.destroy', $item) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-calendar-off fs-1 d-block mb-2 opacity-50"></i>
                        Belum ada jadwal pelajaran yang ditambahkan.
                    </div>
                @endif
            </div>
        </div>

        @if($canManageSchedules && $courses->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tambah Jadwal Pembelajaran</h3>
                </div>
                <div class="card-body">
                    <form id="add-schedule-form" action="{{ route('courses.schedules.store', $courses->first()) }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small">Kelas Digital</label>
                            <select id="schedule-course-select" class="form-select form-select-sm" required>
                                @foreach($courses as $course)
                                    <option value="{{ route('courses.schedules.store', $course) }}">
                                        {{ $course->title }} ({{ $course->classroom?->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Hari</label>
                            <select name="day_of_week" class="form-select form-select-sm" required>
                                @foreach($dayNames as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small">Mulai</label>
                            <input type="time" name="start_time" value="07:00" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small">Selesai</label>
                            <input type="time" name="end_time" value="08:30" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Ruang / Lab</label>
                            <input type="text" name="room" class="form-control form-control-sm" placeholder="Contoh: Lab TKJ 1">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="ti ti-calendar-event me-1 text-primary"></i>
                    {{ $currentMonth->locale('id')->translatedFormat('F Y') }}
                </h3>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('schedule.index', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary" aria-label="Bulan Sebelumnya">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                    <a href="{{ route('schedule.index', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary" aria-label="Bulan Berikutnya">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-3">
                @php
                    $startDayOfWeek = (int) $currentMonth->copy()->startOfMonth()->dayOfWeekIso; // 1..7
                    $daysInMonth = (int) $currentMonth->daysInMonth;
                @endphp
                <div class="d-grid text-center small text-muted fw-semibold mb-2" style="grid-template-columns: repeat(7, 1fr);">
                    <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                </div>
                <div class="d-grid gap-1 text-center" style="grid-template-columns: repeat(7, 1fr);">
                    @for($i = 1; $i < $startDayOfWeek; $i++)
                        <div class="p-2"></div>
                    @endfor
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateObj = $currentMonth->copy()->day($day);
                            $dateKey = $dateObj->format('Y-m-d');
                            $dayEvents = $eventsByDate->get($dateKey, collect());
                            $isToday = $dateObj->isToday();
                        @endphp
                        <div class="rounded p-1 border {{ $isToday ? 'border-primary bg-blue-lt' : 'border-light' }}" style="min-height: 44px;">
                            <div class="small {{ $isToday ? 'fw-bold text-primary' : 'text-dark' }}">{{ $day }}</div>
                            @if($dayEvents->isNotEmpty())
                                <div class="d-flex justify-content-center gap-1 mt-1">
                                    @foreach($dayEvents->take(3) as $ev)
                                        @php
                                            $dotColor = match($ev['type']) {
                                                'assignment' => 'bg-warning',
                                                'holiday' => 'bg-danger',
                                                'school_event' => 'bg-success',
                                                default => 'bg-primary',
                                            };
                                        @endphp
                                        <span class="rounded-circle {{ $dotColor }}" style="width: 6px; height: 6px;"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                <div class="d-flex flex-wrap gap-3 small text-muted mt-3 pt-2 border-top">
                    <span><span class="badge bg-warning me-1">&nbsp;</span> Tenggat Tugas</span>
                    <span><span class="badge bg-primary me-1">&nbsp;</span> Jadwal Kuis</span>
                    <span><span class="badge bg-success me-1">&nbsp;</span> Kegiatan Sekolah</span>
                    <span><span class="badge bg-danger me-1">&nbsp;</span> Hari Libur</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Agenda Bulan Ini</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($calendarEvents as $event)
                    <a href="{{ $event['url'] }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-semibold small">{{ $event['title'] }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($event['date'])->translatedFormat('d M Y') }} &middot; {{ $event['time'] }} &middot; {{ $event['subtitle'] }}
                            </div>
                        </div>
                        @php
                            $badgeClass = match($event['type']) {
                                'assignment' => 'bg-warning-lt',
                                'holiday' => 'bg-danger-lt',
                                'school_event' => 'bg-success-lt',
                                default => 'bg-blue-lt',
                            };
                            $badgeLabel = match($event['type']) {
                                'assignment' => 'Tugas',
                                'holiday' => 'Libur',
                                'school_event' => 'Kegiatan',
                                default => 'Kuis',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ $badgeLabel }}
                        </span>
                    </a>
                @empty
                    <div class="list-group-item text-muted small text-center py-4">
                        Tidak ada agenda tugas atau kuis pada bulan ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const courseSelect = document.getElementById('schedule-course-select');
    const form = document.getElementById('add-schedule-form');
    if (courseSelect && form) {
        courseSelect.addEventListener('change', () => {
            form.action = courseSelect.value;
        });
    }
});
</script>
@endpush
