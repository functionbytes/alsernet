<?php

namespace Modules\Queue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueuePurgeCommand extends Command
{
    protected $signature = 'queue:purge-failed
        {--before= : Delete jobs failed before this date (default: 30 days ago)}
        {--queue= : Filter by queue name}
        {--dry-run : Show count without deleting}';

    protected $description = 'Purge old failed jobs from the database';

    public function handle(): int
    {
        $before = $this->option('before') ?? now()->subDays(30)->toDateTimeString();

        $query = DB::table('failed_jobs')->where('failed_at', '<', $before);

        if ($queue = $this->option('queue')) {
            $query->where('queue', $queue);
        }

        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Found {$count} failed job(s) that would be purged (failed before {$before}).");

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info("No failed jobs found to purge (before {$before}).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Purged {$deleted} failed job(s) that failed before {$before}.");

        return self::SUCCESS;
    }
}
