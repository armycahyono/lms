@extends('layouts.app')

@section('title', "Analisa Siswa — {$student->name}")

@section('content')
@php
    $studentClassroom = $student->classrooms->first()
        ?? $student->studentClassHistories->first()?->classroom;
    $parentNames = $student->parents->pluck('name')->filter()->implode(', ');
    $parentPhone = $student->parents->map(fn ($p) => $p->profile?->phone)->filter()->first();
    $waDigits = $parentPhone ? preg_replace('/[^0-9]/', '', $parentPhone) : '';
    if ($waDigits && str_starts_with($waDigits, '0')) {
        $waDigits = '62' . substr($waDigits, 1);
    }
@endphp

<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('homeroom.index') }}" class="text-decoration-none">Analisa Wali Kelas</a>
                &middot; {{ $activeYear?->name ?? '' }}
            </div>
            <h2 class="page-title">{{ $student->name }}</h2>
            <div class="text-muted small mt-1">
                NISN: <code>{{ $student->username }}</code>
                @if($studentClassroom) &middot; Kelas: {{ $studentClassroom->name }} @endif
                &middot; Orang Tua: {{ $parentNames !== '' ? $parentNames : '-' }}
                ({{ $parentPhone ?: '-' }})
            </div>
        </div>
        <div class="col-auto d-flex gap-2">
            @if($waDigits)
                <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="ti ti-brand-whatsapp me-1"></i> Hubungi Orang Tua
                </a>
            @endif
            <a href="{{ route('homeroom.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>

<div class="row row-cards mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Presensi Sekolah</div>
            <div class="h3 mb-0 mt-1">{{ $schoolAttendances->count() }} <span class="fs-6 text-muted fw-normal">hari</span></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Visitasi Rumah</div>
            <div class="h3 mb-0 mt-1">{{ $visitations->count() }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Insiden &amp; Izin Piket</div>
            <div class="h3 mb-0 mt-1">{{ $incidents->count() }} <span class="fs-6 text-muted fw-normal">/ {{ $permits->count() }} izin</span></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Prestasi &amp; Catatan BK</div>
            <div class="h3 mb-0 mt-1">{{ $bkRecords->count() }}</div>
        </div></div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Riwayat Visitasi (SIAP)</h3></div>
            <div class="card-body">
                @forelse($visitations as $visit)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $visit->visit_type_label ?: ($visit->visit_type ?? 'Visitasi') }}</strong>
                            <span class="badge bg-purple-lt">{{ $visit->status }}</span>
                        </div>
                        <div class="text-muted small">
                            Tanggal: {{ $visit->executed_date?->format('d M Y') ?? $visit->planned_date?->format('d M Y') ?? '-' }}
                            @if($visit->officer_name) &middot; Petugas: {{ $visit->officer_name }} @endif
                        </div>
                        @if($visit->reason)<div class="small mt-1">Alasan: {{ $visit->reason }}</div>@endif
                        @if($visit->result_summary)<div class="small text-muted">Hasil: {{ $visit->result_summary }}</div>@endif
                        @if($visit->follow_up_plan)<div class="small text-primary">Rencana: {{ $visit->follow_up_plan }}</div>@endif
                    </div>
                @empty
                    <div class="text-muted small">Belum ada data visitasi.</div>
                @endforelse
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Catatan Kedisiplinan &amp; Izin Piket (SIAP)</h3></div>
            <div class="card-body">
                @if($incidents->isEmpty() && $permits->isEmpty())
                    <div class="text-muted small">Belum ada catatan insiden atau izin piket.</div>
                @else
                    @foreach($incidents as $inc)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-red-lt">{{ $inc->category ?? 'Insiden' }}</span>
                                <span class="text-muted small">{{ $inc->incident_date?->format('d M Y') ?? '-' }}</span>
                            </div>
                            <div class="small mt-1">{{ $inc->description }}</div>
                            @if($inc->action_taken)<div class="small text-muted">Tindakan: {{ $inc->action_taken }}</div>@endif
                        </div>
                    @endforeach
                    @foreach($permits as $permit)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-azure-lt">{{ $permit->permit_type_label ?: $permit->permit_type }}</span>
                                <span class="text-muted small">{{ $permit->permit_date?->format('d M Y') ?? '-' }}</span>
                            </div>
                            <div class="small mt-1">{{ $permit->reason ?: '-' }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Prestasi, Perkembangan &amp; Layanan BK Non-Rahasia</h3></div>
            <div class="card-body">
                @forelse($bkRecords as $rec)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $rec->title }}</strong>
                            <span class="badge bg-green-lt">{{ $rec->typeLabel() }}</span>
                        </div>
                        <div class="text-muted small">
                            {{ $rec->record_date?->format('d M Y') ?? '-' }}
                            @if($rec->category) &middot; {{ $rec->category }} @endif
                            @if($rec->level) &middot; {{ $rec->level }} @endif
                        </div>
                        @if($rec->summary)<div class="small mt-1">{{ $rec->summary }}</div>@endif
                        @if($rec->recommendation)<div class="small text-primary">Rekomendasi: {{ $rec->recommendation }}</div>@endif
                    </div>
                @empty
                    <div class="text-muted small">Belum ada rekaman prestasi atau catatan perkembangan BK.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Catat Tindak Lanjut Wali Kelas</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('homeroom.follow-ups.store', $student) }}" class="d-flex flex-column gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="follow_up_date" value="{{ now()->toDateString() }}" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Jenis Tindakan</label>
                        <select name="action_type" class="form-select" required>
                            @foreach(\App\Models\HomeroomFollowUp::ACTION_LABELS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="selesai">Selesai</option>
                            <option value="terjadwal">Terjadwal</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Catatan Analisa &amp; Tindak Lanjut</label>
                        <textarea name="notes" rows="3" class="form-control" required placeholder="Tuliskan hasil komunikasi orang tua, pembinaan, atau rencana tindakan..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Riwayat Tindak Lanjut Wali Kelas</h3></div>
            <div class="card-body">
                @forelse($followUps as $fu)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-blue-lt">{{ $fu->actionLabel() }}</span>
                            <span class="text-muted small">{{ $fu->follow_up_date?->format('d M Y') }}</span>
                        </div>
                        <div class="small mt-1">{{ $fu->notes }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Oleh: {{ $fu->teacher?->name ?? '-' }} &middot; {{ ucfirst($fu->status) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada catatan tindak lanjut.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Presensi Mapel Terbaru (SIAP)</h3></div>
            <div class="card-body">
                @forelse($subjectAttendances->take(10) as $sa)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                        <div>
                            <div class="fw-medium">{{ $sa->subject?->name ?? 'Mapel' }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $sa->attendance_date?->format('d M Y') }}</div>
                        </div>
                        <span class="badge bg-secondary-lt">{{ $sa->statusLabel() }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada data presensi mapel.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
