<?php

use App\Models\SyncCheckpoint;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$isGroupEnabled = static function (string $groupKey): bool {
    if (! Schema::hasTable('sync_checkpoints')) {
        return true;
    }

    $checkpoint = SyncCheckpoint::where('group_key', $groupKey)->first();

    return $checkpoint ? $checkpoint->auto_sync_enabled : true;
};

Schedule::command('lms:sync-siap --group=master')
    ->dailyAt('02:00')
    ->when(fn () => $isGroupEnabled('master'))
    ->withoutOverlapping();

Schedule::command('lms:sync-siap --group=students_calendar')
    ->everySixHours()
    ->when(fn () => $isGroupEnabled('students_calendar'))
    ->withoutOverlapping();

Schedule::command('lms:sync-siap --group=transactional')
    ->everyThirtyMinutes()
    ->when(fn () => $isGroupEnabled('transactional'))
    ->withoutOverlapping();

// Backup database harian otomatis dengan retensi 7h / 4m / 6b
Schedule::command('app:backup --keep-days=7 --keep-weekly=4 --keep-monthly=6')
    ->dailyAt('02:30')
    ->withoutOverlapping();
