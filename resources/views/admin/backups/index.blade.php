@extends('layouts.app')

@section('title', 'Backup Data LMS')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Pemeliharaan Sistem</div>
            <h2 class="page-title">Backup Data LMS</h2>
        </div>
        <div class="col-auto">
            <form method="POST" action="{{ route('admin.backups.store') }}" class="d-flex flex-wrap align-items-center gap-3">
                @csrf
                <label class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="checkbox" name="with_storage" value="1">
                    <span class="form-check-label small">Sertakan file storage</span>
                </label>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-database-export me-1"></i> Backup Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body py-3 text-muted small">
        Jadwal otomatis berjalan setiap hari pukul <strong>02:30 WIB</strong>. Retensi: 7 hari terakhir, 4 minggu (Minggu), 6 bulan (tanggal 1).
    </div>
</div>

<div class="card mb-4 border-warning">
    <div class="card-header">
        <h3 class="card-title mb-0">Pulihkan dari File Backup (.sql / .sql.gz)</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.backups.restore-upload') }}" enctype="multipart/form-data" class="row g-2 align-items-center" onsubmit="return confirm('PERINGATAN: Pemulihan akan menimpa seluruh data database LMS saat ini. Pastikan Anda sudah yakin. Lanjutkan?')">
            @csrf
            <div class="col-md-8">
                <input type="file" name="backup_file" accept=".sql,.gz,.sql.gz,.sqlite.gz" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-warning w-100">
                    <i class="ti ti-database-import me-1"></i> Unggah &amp; Pulihkan Database
                </button>
            </div>
        </form>
        <div class="text-muted small mt-2">
            Pemulihan akan menggantikan isi database saat ini dengan isi dari file backup yang dipilih.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar File Backup ({{ $backups->count() }})</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Jenis</th>
                    <th>Ukuran</th>
                    <th>Tanggal Dibuat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td><code>{{ $backup['name'] }}</code></td>
                        <td>
                            <span class="badge {{ $backup['type'] === 'Database' ? 'bg-blue-lt text-blue' : 'bg-purple-lt text-purple' }}">
                                {{ $backup['type'] }}
                            </span>
                        </td>
                        <td>{{ $backup['size'] }}</td>
                        <td class="text-muted small">{{ $backup['created_at'] }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                @if($backup['type'] === 'Database')
                                    <form method="POST" action="{{ route('admin.backups.restore', $backup['name']) }}" onsubmit="return confirm('PERINGATAN: Memulihkan {{ $backup['name'] }} akan menimpa seluruh data database saat ini. Lanjutkan?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="ti ti-history me-1"></i> Pulihkan
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.backups.download', $backup['name']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-download me-1"></i> Unduh
                                </a>
                                <form method="POST" action="{{ route('admin.backups.destroy', $backup['name']) }}" onsubmit="return confirm('Hapus file backup ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            Belum ada file backup. Klik <strong>Backup Sekarang</strong> untuk membuat cadangan pertama.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
