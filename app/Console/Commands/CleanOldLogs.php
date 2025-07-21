<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanOldLogs extends Command
{
    protected $signature = 'logs:clean-old';
    protected $description = 'Remove log files older than 30 days';

    public function handle()
    {
        $logPath = storage_path('logs');
        $cutoffDate = Carbon::now()->subDays(30);
        $deletedCount = 0;

        foreach (File::glob("$logPath/*.log") as $logFile) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($logFile));

            if ($lastModified->lt($cutoffDate)) {
                File::delete($logFile);
                $deletedCount++;
                $this->info("Deleted: " . basename($logFile));
            }
        }

        $this->info("Successfully deleted {$deletedCount} old log file(s)");
        return 0;
    }
}
