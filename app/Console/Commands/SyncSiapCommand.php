<?php

namespace App\Console\Commands;

use App\Services\SiapSyncService;
use Illuminate\Console\Command;

class SyncSiapCommand extends Command
{
    protected $signature = 'lms:sync-siap
        {--group= : Kelompok sinkron (master, students_calendar, transactional)}
        {--scope=window : Cakupan tahun ajaran (window = 3 tahun terakhir/aktif/berikutnya, all = semua tahun)}
        {--since= : Incremental sync timestamp (ISO-8601)}
        {--full : Paksa sinkron penuh tanpa checkpoint}';

    protected $description = 'Sinkronisasi data SIAP ke LMS (3 tahun ajaran operasional atau menyeluruh)';

    public function handle(SiapSyncService $syncService): int
    {
        $group = $this->option('group');
        $full = (bool) $this->option('full');
        $scope = (string) ($this->option('scope') ?: 'window');

        if (is_string($group) && $group !== '') {
            $this->info("Menjalankan sinkronisasi kelompok '{$group}' (scope: {$scope}".($full ? ', full' : ', incremental').')...');
            $summary = $syncService->syncGroup($group, $full, 'siap_scheduled', $scope);
        } else {
            $since = $this->option('since');
            $this->info($since ? "Menjalankan incremental sync dari SIAP sejak {$since} (scope: {$scope})..." : "Menjalankan sync penuh dari SIAP (scope: {$scope})...");
            $summary = $syncService->syncAll(is_string($since) ? $since : null, $scope);
        }

        foreach ($summary as $entity => $count) {
            $this->line(" - {$entity}: {$count} data");
        }

        $this->info('Sinkronisasi SIAP selesai.');

        return self::SUCCESS;
    }
}
