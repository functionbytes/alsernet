<?php

namespace Modules\Reviews\Console;

use Illuminate\Console\Command;
use Modules\Reviews\Models\GeneratedReport;

class CleanExpiredReportsCommand extends Command
{
    protected $signature = 'reviews:reports-cleanup {--days=30 : Delete reports older than this many days}';

    protected $description = 'Clean up expired and old generated reports';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up reports older than {$days} days...");

        $expiredReports = GeneratedReport::query()
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        if ($expiredReports->isEmpty()) {
            $this->info('No expired reports found.');

            return self::SUCCESS;
        }

        $deletedCount = 0;
        $filesDeletedCount = 0;

        foreach ($expiredReports as $report) {
            if ($report->deleteFile()) {
                $filesDeletedCount++;
            }

            $report->delete();
            $deletedCount++;
        }

        $this->info("Deleted {$deletedCount} report records.");
        $this->info("Deleted {$filesDeletedCount} report files.");

        return self::SUCCESS;
    }
}
