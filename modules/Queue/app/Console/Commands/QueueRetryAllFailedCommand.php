<?php

namespace Modules\Queue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueRetryAllFailedCommand extends Command
{
    protected $signature = 'queue:retry-all
        {--queue= : Filter by queue name}
        {--before= : Only retry jobs failed before this datetime (e.g. "2024-01-01 00:00:00")}
        {--dry-run : Show count without retrying}';

    protected $description = 'Retry all failed jobs, optionally filtered by queue';

    public function handle(): int
    {
        $query = DB::table('failed_jobs');

        if ($queue = $this->option('queue')) {
            $query->where('queue', $queue);
        }

        if ($before = $this->option('before')) {
            $query->where('failed_at', '<', $before);
        }

        $jobs = $query->get(['uuid', 'queue', 'failed_at']);

        if ($this->option('dry-run')) {
            $this->info("Found {$jobs->count()} failed job(s) matching the criteria.");
            $this->table(['UUID', 'Queue', 'Failed At'], $jobs->map(fn ($j) => [$j->uuid, $j->queue, $j->failed_at]));

            return self::SUCCESS;
        }

        if ($jobs->isEmpty()) {
            $this->info('No failed jobs found matching the criteria.');

            return self::SUCCESS;
        }

        $retried = 0;
        foreach ($jobs as $job) {
            Artisan::call('queue:retry', ['id' => $job->uuid]);
            $retried++;
        }

        $this->info("Retried {$retried} failed job(s).");

        return self::SUCCESS;
    }
}
