<?php

namespace Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WebhookPerformanceCommand extends Command
{
    protected $signature = 'webhooks:performance {--period=24}';

    protected $description = 'Display webhook processing performance metrics';

    public function handle(): int
    {
        $period = (int) $this->option('period');
        $since = now()->subHours($period);

        $this->info("Webhook Performance Report - Last {$period} hours");
        $this->line(str_repeat('=', 70));

        $this->displayQueueMetrics();
        $this->displayJobMetrics($since);
        $this->displayThroughputMetrics($since);
        $this->displayMemoryMetrics();
        $this->displayFailedJobs();

        return self::SUCCESS;
    }

    private function displayQueueMetrics(): void
    {
        $this->newLine();
        $this->info('📊 Queue Status');
        $this->line(str_repeat('-', 70));

        $queues = ['webhooks', 'attachments', 'default'];

        foreach ($queues as $queue) {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $this->line("  {$queue}: {$pending} pending jobs");
        }
    }

    private function displayJobMetrics($since): void
    {
        $this->newLine();
        $this->info('⚡ Job Processing Metrics');
        $this->line(str_repeat('-', 70));

        // Get completed jobs from job_batches
        $batches = DB::table('job_batches')
            ->where('created_at', '>=', $since)
            ->get();

        $totalJobs = $batches->sum('total_jobs');
        $completedJobs = $batches->sum(function ($batch) {
            return $batch->total_jobs - $batch->pending_jobs;
        });
        $failedJobs = $batches->sum('failed_jobs');

        $this->line("  Total Jobs: {$totalJobs}");
        $this->line("  Completed: {$completedJobs}");
        $this->line("  Failed: {$failedJobs}");

        if ($totalJobs > 0) {
            $successRate = round(($completedJobs / $totalJobs) * 100, 2);
            $this->line("  Success Rate: {$successRate}%");
        }
    }

    private function displayThroughputMetrics($since): void
    {
        $this->newLine();
        $this->info('🚀 Throughput Analysis');
        $this->line(str_repeat('-', 70));

        // Parse logs for batch processing metrics
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            $this->warn('  Log file not found');

            return;
        }

        $logs = file_get_contents($logFile);
        preg_match_all('/\[BATCH METRICS\].*?throughput_msgs_per_sec":([0-9.]+)/', $logs, $matches);

        if (! empty($matches[1])) {
            $throughputs = array_map('floatval', $matches[1]);
            $avgThroughput = round(array_sum($throughputs) / count($throughputs), 2);
            $maxThroughput = round(max($throughputs), 2);

            $this->line("  Average Throughput: {$avgThroughput} messages/sec");
            $this->line("  Peak Throughput: {$maxThroughput} messages/sec");
            $this->line('  Total Batches Analyzed: '.count($throughputs));
        } else {
            $this->warn('  No throughput data available in logs');
        }
    }

    private function displayMemoryMetrics(): void
    {
        $this->newLine();
        $this->info('💾 Memory Usage');
        $this->line(str_repeat('-', 70));

        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return;
        }

        $logs = file_get_contents($logFile);
        preg_match_all('/memory_peak_mb":([0-9.]+)/', $logs, $matches);

        if (! empty($matches[1])) {
            $memoryPeaks = array_map('floatval', $matches[1]);
            $avgMemory = round(array_sum($memoryPeaks) / count($memoryPeaks), 2);
            $maxMemory = round(max($memoryPeaks), 2);

            $this->line("  Average Peak Memory: {$avgMemory} MB");
            $this->line("  Max Peak Memory: {$maxMemory} MB");

            if ($maxMemory > 100) {
                $this->warn('  ⚠️  High memory usage detected - consider reducing batch sizes');
            }
        }
    }

    private function displayFailedJobs(): void
    {
        $this->newLine();
        $this->info('❌ Recent Failed Jobs');
        $this->line(str_repeat('-', 70));

        $failed = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get();

        if ($failed->isEmpty()) {
            $this->line('  No failed jobs ✅');

            return;
        }

        foreach ($failed as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';

            $this->line("  • {$jobClass}");
            $this->line("    Failed: {$job->failed_at}");
            $this->line('    Exception: '.substr($job->exception, 0, 100).'...');
            $this->newLine();
        }
    }
}
