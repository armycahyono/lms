<?php

namespace App\Console\Commands;

use App\Services\SiapSyncService;
use Illuminate\Console\Command;

class SyncSiapCommand extends Command
{
    protected $signature = 'lms:sync-siap {--since= : Incremental sync timestamp (ISO-8601)}';

    protected $description = 'Sinkronisasi data master (tahun ajaran, mapel, kelas, guru, siswa) dari SIAP';

    public function handle(SiapSyncService $syncService): int
    {
        $since = $this->option('since');
        $this->info($since ? "Menjalankan incremental sync dari SIAP sejak {$since}..." : 'Menjalankan initial sync penuh dari SIAP...');

        $summary = $syncService->syncAll(is_string($since) ? $since : null);

        foreach ($summary as $entity => $count) {
            $this->line(" - {$entity}: {$count} data");
        }

        $this->info('Sinkronisasi SIAP selesai.');

        return self::SUCCESS;
    }
}
