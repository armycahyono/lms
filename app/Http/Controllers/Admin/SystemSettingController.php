<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncCheckpoint;
use App\Models\SyncLog;
use App\Services\SiapSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SystemSettingController extends Controller
{
    public function index(Request $request, SiapSyncService $syncService): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tab = in_array($request->query('tab'), ['sync', 'history', 'connection'], true)
            ? (string) $request->query('tab')
            : 'sync';
        $source = trim((string) $request->query('source', ''));
        $status = trim((string) $request->query('status', ''));

        $checkpoints = $syncService->ensureCheckpointsExist();

        $logs = SyncLog::query()
            ->when($source !== '', fn ($q) => $q->where('source', $source))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.settings.index', [
            'tab' => $tab,
            'source' => $source,
            'status' => $status,
            'checkpoints' => $checkpoints,
            'syncMeta' => SiapSyncService::SYNC_GROUPS,
            'logs' => $logs,
            'siapUrl' => (string) config('services.siap.url', 'http://siap.test'),
            'hasSiapToken' => trim((string) config('services.siap.token', '')) !== '',
        ]);
    }

    public function syncGroup(Request $request, SiapSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'group' => ['required', 'string', 'in:all,all_years_full,master,students_calendar,transactional'],
            'mode' => ['nullable', 'string', 'in:incremental,full'],
        ]);

        $full = ($validated['mode'] ?? 'incremental') === 'full';
        $group = $validated['group'];

        try {
            if ($group === 'all_years_full') {
                $summary = $syncService->syncAll(null, 'all', 'siap_manual', 'manual.sync_all_years');
                $detail = collect($summary)->map(fn ($count, $key) => "{$key}: {$count}")->join(', ');

                return back()->with('status', "Sinkronisasi menyeluruh (semua tahun ajaran) berhasil: {$detail}.");
            }

            if ($group === 'all') {
                $combined = [];
                foreach (array_keys(SiapSyncService::SYNC_GROUPS) as $groupKey) {
                    $combined = array_merge($combined, $syncService->syncGroup($groupKey, $full, 'siap_manual', 'window'));
                }
                $detail = collect($combined)->map(fn ($count, $key) => "{$key}: {$count}")->join(', ');

                return back()->with('status', 'Sinkronisasi rutin 3 tahun ajaran selesai ('.($full ? 'Penuh' : 'Incremental')."): {$detail}.");
            }

            $summary = $syncService->syncGroup($group, $full, 'siap_manual', 'window');
            $label = SiapSyncService::SYNC_GROUPS[$group]['label'] ?? $group;
            $detail = collect($summary)->map(fn ($count, $key) => "{$key}: {$count}")->join(', ');

            return back()->with('status', "Sinkronisasi {$label} (3 tahun ajaran) berhasil (".($full ? 'Penuh' : 'Incremental')."): {$detail}.");
        } catch (Throwable $e) {
            return back()->withErrors([
                'siap' => 'Gagal sinkronisasi dengan SIAP: '.$e->getMessage(),
            ]);
        }
    }

    public function toggleCheckpoint(Request $request, SyncCheckpoint $checkpoint): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $checkpoint->update([
            'auto_sync_enabled' => ! $checkpoint->auto_sync_enabled,
        ]);

        $state = $checkpoint->auto_sync_enabled ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Auto-sync untuk {$checkpoint->label} berhasil {$state}.");
    }
}
