<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiapSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SiapSyncController extends Controller
{
    public function sync(Request $request, SiapSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $summary = $syncService->syncAll(null, 'window', 'siap_manual', 'manual.sync_all');

            $detail = collect($summary)
                ->map(fn ($count, $key) => "{$key}: {$count}")
                ->join(', ');

            return back()->with('status', "Sinkronisasi SIAP berhasil ({$detail}).");
        } catch (Throwable $e) {
            return back()->withErrors([
                'siap' => 'Gagal sinkronisasi dengan SIAP: '.$e->getMessage(),
            ]);
        }
    }
}
