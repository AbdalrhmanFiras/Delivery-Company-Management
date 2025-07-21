<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



app()->booted(function () {
    $schedule = app(Schedule::class);

    $schedule->command('model:prune')->cron('0 0 1 */6 *');
});


Artisan::command('logs:clean', function () {
    $files = File::allFiles(storage_path('logs'));

    foreach ($files as $file) {
        if ($file->getCTime() < now()->subDays(30)->timestamp) {
            unlink($file->getRealPath());
        }
    }

    $this->info('Old logs cleaned successfully.');
});
