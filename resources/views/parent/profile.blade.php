@extends('layouts.app')

@section('title', 'Profil & Data Anak')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Portal Orang Tua</div>
            <h2 class="page-title">Profil Orang Tua &amp; Data Anak</h2>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Akun Orang Tua / Wali</h3></div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Nama:</span> <div class="fw-bold">{{ $parent->name }}</div></div>
                <div class="mb-2"><span class="text-muted small">Username:</span> <div>{{ $parent->username }}</div></div>
                <div><span class="text-muted small">Email:</span> <div>{{ $parent->email ?? '-' }}</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Preferensi Notifikasi Presensi</h3></div>
            <div class="card-body">
                <form action="{{ route('parent.preferences.update') }}" method="POST" class="d-grid gap-2">
                    @csrf
                    <label class="form-check">
                        <input type="radio" name="parent_notify_mode" value="alerts_only" class="form-check-input" @checked(($parent->parent_notify_mode ?? 'alerts_only') === 'alerts_only')>
                        <span class="form-check-label">Hanya Alert Penting (Terlambat, Izin, Sakit, Alpa, atau Perubahan Status)</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="parent_notify_mode" value="all" class="form-check-input" @checked($parent->parent_notify_mode === 'all')>
                        <span class="form-check-label">Semua Status Presensi (Termasuk Hadir Tepat Waktu)</span>
                    </label>
                    <button type="submit" class="btn btn-primary mt-2">Simpan Preferensi</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Daftar Anak Terhubung ({{ $children->count() }})</h3></div>
            <div class="list-group list-group-flush">
                @forelse($children as $child)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $child->name }}</div>
                            <div class="text-muted small">
                                NISN: {{ $child->username }} &middot; Hubungan: {{ ucfirst($child->pivot->relationship ?? 'Wali') }}
                                @if($child->classrooms->isNotEmpty())
                                    &middot; Kelas: {{ $child->classrooms->first()->name }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('parent.index', ['child_id' => $child->id]) }}" class="btn btn-sm btn-outline-primary">Pantau</a>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4 small">Belum ada anak terhubung.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
