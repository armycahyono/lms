@extends('layouts.app')

@section('title', 'Analisa Siswa Binaan (Wali Kelas)')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                {{ $activeYear ? "Tahun Ajaran {$activeYear->name}" : 'Pemantauan Siswa' }}
            </div>
            <h2 class="page-title">Analisa &amp; Tindak Lanjut Siswa Binaan</h2>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('homeroom.index') }}" class="row g-2 mb-3">
    <div class="col-sm-5">
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama atau NISN siswa...">
        </div>
    </div>
    <div class="col-sm-4">
        <select name="classroom_id" class="form-select">
            <option value="">Semua Kelas Binaan</option>
            @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>{{ $classroom->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-3 col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Orang Tua &amp; Telp</th>
                    <th class="text-center">Telat / Tidak Hadir</th>
                    <th class="text-center">Insiden &amp; Izin</th>
                    <th class="text-center">Visitasi &amp; BK</th>
                    <th class="text-center">Tindak Lanjut</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $studentClassroom = $student->classrooms->first()
                            ?? $student->studentClassHistories->first()?->classroom;
                        $parentNames = $student->parents->pluck('name')->filter()->implode(', ');
                        $parentPhone = $student->parents->map(fn ($p) => $p->profile?->phone)->filter()->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $student->name }}</div>
                            <div class="text-muted small">
                                <code>{{ $student->username }}</code>
                                @if($studentClassroom)
                                    &middot; {{ $studentClassroom->name }}
                                @endif
                            </div>
                        </td>
                        <td class="small">
                            <div class="text-dark">{{ $parentNames !== '' ? $parentNames : '-' }}</div>
                            <div class="text-muted">{{ $parentPhone ?: '-' }}</div>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-warning-lt">{{ $student->late_count }} Telat</span>
                            <span class="badge bg-danger-lt">{{ $student->absent_count + $student->subject_absent_count }} Absen</span>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-red-lt">{{ $student->incidents_count }} Insiden</span>
                            <span class="badge bg-azure-lt">{{ $student->permits_count }} Izin</span>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-purple-lt">{{ $student->visitations_count }} Visitasi</span>
                            <span class="badge bg-green-lt">{{ $student->bk_records_count }} BK</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-blue-lt">{{ $student->follow_ups_count }} Catatan</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('homeroom.show', $student) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-chart-bar me-1"></i> Analisa
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada siswa pada kelas binaan untuk tahun ajaran ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
