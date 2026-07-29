<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorEmailJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-jobs:monitor {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor email queue jobs status and performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');

        $stats = $this->gatherJobStatistics();

        match ($format) {
            'json' => $this->outputJson($stats),
            'csv' => $this->outputCsv($stats),
            default => $this->outputTable($stats),
        };

        return 0;
    }

    private function gatherJobStatistics(): array
    {
        // Estadísticas generales
        $pendingCount = DB::table('jobs')
            ->where('queue', 'emails')
            ->whereNull('reserved_at')
            ->count();

        $processingCount = DB::table('jobs')
            ->where('queue', 'emails')
            ->whereNotNull('reserved_at')
            ->count();

        $failedCount = DB::table('failed_jobs')->count();

        // Información de jobs pendientes
        $pendingJobs = DB::table('jobs')
            ->where('queue', 'emails')
            ->select(['id', 'payload', 'created_at', 'attempts'])
            ->limit(10)
            ->get();

        // Información de jobs fallidos
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->select(['id', 'exception', 'failed_at'])
            ->get();

        // Tiempo promedio de jobs completados (desde logs)
        $logPath = storage_path('logs/email-jobs.log');
        $avgDuration = $this->calculateAverageDurationFromLogs($logPath);

        return [
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'pending' => $pendingCount,
                'processing' => $processingCount,
                'failed' => $failedCount,
                'total' => $pendingCount + $processingCount + $failedCount,
            ],
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'average_duration_ms' => $avgDuration,
        ];
    }

    private function calculateAverageDurationFromLogs(string $logPath): ?float
    {
        if (! file_exists($logPath)) {
            return null;
        }

        $durations = [];
        $lines = array_reverse(file($logPath));

        foreach ($lines as $line) {
            if (preg_match('"duration_ms":(\d+(?:\.\d+)?)', $line, $matches)) {
                $durations[] = (float) $matches[1];

                if (count($durations) >= 100) {
                    break;
                }
            }
        }

        if (empty($durations)) {
            return null;
        }

        return round(array_sum($durations) / count($durations), 2);
    }

    private function outputTable(array $stats): void
    {
        $this->info('=== Email Queue Status ===');
        $this->line('Timestamp: '.$stats['timestamp']);
        $this->newLine();

        // Summary
        $this->info('Queue Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Pending', $stats['summary']['pending']],
                ['Processing', $stats['summary']['processing']],
                ['Failed', $stats['summary']['failed']],
                ['Total', $stats['summary']['total']],
            ]
        );

        // Average duration
        if ($stats['average_duration_ms'] !== null) {
            $this->line('Average Job Duration: '.$stats['average_duration_ms'].' ms');
        }

        // Pending jobs
        if ($stats['pending_jobs']->isNotEmpty()) {
            $this->newLine();
            $this->info('Recent Pending Jobs:');
            $this->table(
                ['ID', 'Created At', 'Attempts', 'Type'],
                $stats['pending_jobs']->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $displayName = $payload['displayName'] ?? 'Unknown';
                    $displayName = basename($displayName);

                    return [
                        $job->id,
                        $job->created_at,
                        $job->attempts,
                        $displayName,
                    ];
                })->toArray()
            );
        }

        // Failed jobs
        if ($stats['failed_jobs']->isNotEmpty()) {
            $this->newLine();
            $this->warn('Recent Failed Jobs:');
            $this->table(
                ['ID', 'Failed At', 'Error Type'],
                $stats['failed_jobs']->map(function ($job) {
                    $errorType = 'Unknown';

                    if (preg_match('/^([^\:]+)/', $job->exception, $matches)) {
                        $errorType = basename($matches[1]);
                    }

                    return [
                        $job->id,
                        $job->failed_at,
                        $errorType,
                    ];
                })->toArray()
            );

            $this->warn("\nTo see detailed errors, check: storage/logs/email-jobs.log");
        }

        $this->newLine();
        $this->line('To view detailed logs: tail -f '.storage_path('logs/email-jobs.log'));
    }

    private function outputJson(array $stats): void
    {
        $output = [
            'timestamp' => $stats['timestamp'],
            'summary' => $stats['summary'],
            'average_duration_ms' => $stats['average_duration_ms'],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function outputCsv(array $stats): void
    {
        $this->line('Metric,Value');
        $this->line('Timestamp,'.$stats['timestamp']);
        $this->line('Pending,'.$stats['summary']['pending']);
        $this->line('Processing,'.$stats['summary']['processing']);
        $this->line('Failed,'.$stats['summary']['failed']);
        $this->line('Total,'.$stats['summary']['total']);
        $this->line('Average Duration (ms),'.$stats['average_duration_ms'] ?? 'N/A');
    }
}
