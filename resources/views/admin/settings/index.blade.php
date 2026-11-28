@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Administrasi LMS</div>
            <h2 class="page-title">Pengaturan Sistem &amp; Sinkronisasi SIAP</h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <form action="{{ route('admin.settings.sync-group') }}" method="POST">
                @csrf
                <input type="hidden" name="group" value="all">
                <input type="hidden" name="mode" value="incremental">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-refresh me-1"></i> Sync Rutin 3 Tahun (Incremental)
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Menu Pengaturan Sistem">
        <a href="{{ route('admin.settings.index', ['tab' => 'sync']) }}" class="lms-pill-tab {{ $tab === 'sync' ? 'active' : '' }}">
            <i class="ti ti-arrows-exchange"></i>
            <span>Kelompok Sinkronisasi</span>
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'history']) }}" class="lms-pill-tab {{ $tab === 'history' ? 'active' : '' }}">
            <i class="ti ti-history"></i>
            <span>Riwayat Sinkronisasi</span>
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'connection']) }}" class="lms-pill-tab {{ $tab === 'connection' ? 'active' : '' }}">
            <i class="ti ti-plug-connected"></i>
            <span>Koneksi &amp; Webhook</span>
        </a>
    </div>
</div>

@if($tab === 'sync')
    <div class="row row-cards">
        @foreach($checkpoints as $checkpoint)
            @php
                $meta = $syncMeta[$checkpoint->group_key] ?? [];
                $intervalLabel = match (true) {
                    $checkpoint->schedule_minutes >= 1440 => '1x Sehari (02:00)',
                    $checkpoint->schedule_minutes >= 360 => 'Setiap 6 Jam',
                    default => "Setiap {$checkpoint->schedule_minutes} Menit",
                };
            @endphp
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <h3 class="card-title mb-0">{{ $checkpoint->label }}</h3>
                        <span class="badge {{ $checkpoint->auto_sync_enabled ? 'bg-success-lt text-success' : 'bg-secondary-lt text-muted' }}">
                            {{ $checkpoint->auto_sync_enabled ? 'Auto Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="text-muted small">{{ $meta['description'] ?? '' }}</div>

                        <div class="p-2 rounded bg-light-subtle border small">
                            <div><strong>Jadwal Otomatis:</strong> {{ $intervalLabel }}</div>
                            <div>
                                <strong>Sync Terakhir:</strong>
                                @if($checkpoint->last_synced_at)
                                    {{ $checkpoint->last_synced_at->format('d M Y H:i') }}
                                    <span class="badge bg-azure-lt ms-1">{{ $checkpoint->last_sync_mode ?? 'full' }}</span>
                                @else
                                    <span class="text-muted">Belum pernah</span>
                                @endif
                            </div>
                        </div>

                        @if(!empty($checkpoint->last_summary_json))
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($checkpoint->last_summary_json as $entity => $count)
                                    <span class="badge bg-blue-lt">{{ str_replace('_', ' ', $entity) }}: {{ $count }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-auto d-flex flex-wrap gap-2 pt-2 border-top">
                            <form action="{{ route('admin.settings.sync-group') }}" method="POST">
                                @csrf
                                <input type="hidden" name="group" value="{{ $checkpoint->group_key }}">
                                <input type="hidden" name="mode" value="incremental">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-refresh me-1"></i> Sync Terbaru
                                </button>
                            </form>

                            <form action="{{ route('admin.settings.sync-group') }}" method="POST">
                                @csrf
                                <input type="hidden" name="group" value="{{ $checkpoint->group_key }}">
                                <input type="hidden" name="mode" value="full">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    Sync Penuh
                                </button>
                            </form>

                            <form action="{{ route('admin.settings.checkpoints.toggle', $checkpoint) }}" method="POST" class="ms-auto">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $checkpoint->auto_sync_enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    {{ $checkpoint->auto_sync_enabled ? 'Matikan Auto' : 'Aktifkan Auto' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-4 border-warning">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="card-title mb-1 text-dark">
                    <i class="ti ti-database-import text-warning me-1"></i>
                    Sinkronisasi Menyeluruh (Semua Tahun Ajaran)
                </h3>
                <div class="text-muted small">
                    Sinkronisasi rutin di atas hanya memproses <strong>3 tahun ajaran</strong> (tahun lalu, aktif, dan tahun berikutnya).
                    Gunakan tombol ini hanya jika benar-benar dibutuhkan untuk migrasi awal atau pemulihan seluruh arsip tahun ajaran.
                </div>
            </div>
            <form action="{{ route('admin.settings.sync-group') }}" method="POST" class="flex-shrink-0">
                @csrf
                <input type="hidden" name="group" value="all_years_full">
                <input type="hidden" name="mode" value="full">
                <button type="submit" class="btn btn-warning text-dark fw-semibold" onclick="return confirm('Sinkronisasi menyeluruh akan menarik seluruh tahun ajaran dari SIAP. Lanjutkan?')">
                    <i class="ti ti-alert-triangle me-1"></i> Jalankan Sync Menyeluruh
                </button>
            </form>
        </div>
    </div>
@elseif($tab === 'history')
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Riwayat Sinkronisasi SIAP</h3>
            <form method="GET" action="{{ route('admin.settings.index') }}" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="tab" value="history">
                <select name="source" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Semua Sumber</option>
                    <option value="siap_manual" @selected($source === 'siap_manual')>Manual Admin</option>
                    <option value="siap_scheduled" @selected($source === 'siap_scheduled')>Terjadwal (Cron)</option>
                    <option value="siap_webhook" @selected($source === 'siap_webhook')>Webhook Real-Time</option>
                    <option value="siap_parent" @selected($source === 'siap_parent')>Portal Orang Tua</option>
                    <option value="siap" @selected($source === 'siap')>CLI / Umum</option>
                </select>
                <select name="status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Semua Status</option>
                    <option value="success" @selected($status === 'success')>Success</option>
                    <option value="failed" @selected($status === 'failed')>Failed</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Sumber</th>
                        <th>Event / Kelompok</th>
                        <th>Status</th>
                        <th>Ringkasan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-muted text-nowrap">
                                {{ $log->created_at?->format('d M Y H:i:s') }}
                            </td>
                            <td>
                                <span class="badge bg-azure-lt">{{ $log->source }}</span>
                            </td>
                            <td class="small fw-semibold">{{ $log->event_type }}</td>
                            <td>
                                <span class="badge {{ $log->status === 'success' ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="small">
                                @if($log->error_message)
                                    <span class="text-danger">{{ $log->error_message }}</span>
                                @elseif(!empty($log->summary_json))
                                    <span class="text-muted">
                                        {{ collect($log->summary_json)->map(fn ($v, $k) => is_scalar($v) ? "{$k}: {$v}" : null)->filter()->take(6)->join(', ') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat sinkronisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@else
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Konfigurasi Koneksi SIAP</h3></div>
                <div class="card-body d-flex flex-column gap-2 small">
                    <div><strong>SIAP Base URL:</strong> <code>{{ $siapUrl }}</code></div>
                    <div>
                        <strong>Integration Token:</strong>
                        <span class="badge {{ $hasSiapToken ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                            {{ $hasSiapToken ? 'Terpasang' : 'Belum Diatur' }}
                        </span>
                    </div>
                    <div><strong>Endpoint Webhook LMS:</strong> <code>POST {{ url('/api/v1/webhooks/siap') }}</code></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Event Webhook Real-Time Aktif</h3></div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li><code>academic_year.activated</code> — Pergantian tahun ajaran aktif di SIAP</li>
                        <li><code>student.updated</code> / <code>student.mutated</code> — Perubahan, mutasi rombel, atau siswa keluar/lulus</li>
                        <li><code>classroom.updated</code> — Pembaruan data rombel kelas</li>
                        <li><code>assignment.updated</code> — Penugasan guru mengajar</li>
                        <li><code>attendance.recorded</code> / <code>attendance.updated</code> — Presensi biometrik/harian siswa</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
