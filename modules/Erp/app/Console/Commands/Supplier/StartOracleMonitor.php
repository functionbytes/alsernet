<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Jobs\MonitorOracleChanges;

class StartOracleMonitor extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:monitor-oracle
                            {--once : Run monitor once and exit (useful for testing)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Start continuous real-time monitoring of Oracle ERP changes (Supplier sync)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔵 Starting Oracle ERP change monitor...');
        $this->newLine();

        // Show configuration info
        $this->info('Configuration:');
        $this->line('  • Queue: <fg=cyan>oracle-monitor</>');
        $this->line('  • Timeout: <fg=cyan>3600 seconds</> (1 hour max, redespatches before)');
        $this->line('  • Tries: <fg=cyan>2</> (rarely needed, job redespatches itself)');
        $this->line('  • Interval: <fg=cyan>'.config('erp.oracle_monitor_interval', 30).' seconds</> between cycles');
        $this->newLine();

        // If --once flag is set, run synchronously for testing
        if ($this->option('once')) {
            $this->info('📋 Running monitor ONCE (synchronous mode for testing)');
            $this->newLine();

            try {
                $job = new MonitorOracleChanges;
                $job->handle();

                $this->info('✅ Monitor completed successfully');

                return self::SUCCESS;

            } catch (\Exception $e) {
                $this->error('❌ Monitor failed: '.$e->getMessage());
                Log::error('MonitorOracleChanges failed in --once mode', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return self::FAILURE;
            }
        }

        // Normal mode: dispatch job to queue for continuous monitoring
        $this->info('📤 Dispatching MonitorOracleChanges job to queue...');
        $this->newLine();

        try {
            MonitorOracleChanges::dispatch();

            $this->info('✅ Job dispatched successfully!');
            $this->newLine();
            $this->info('🎯 What happens next:');
            $this->line('  1. Job is queued on <fg=cyan>oracle-monitor</> queue');
            $this->line('  2. A worker process will pick it up and run it');
            $this->line('  3. The job runs indefinitely, continuously monitoring Oracle');
            $this->line('  4. Changes are synced in real-time to Supplier module');
            $this->line('  5. Health checks logged every 10 cycles');
            $this->newLine();

            $this->info('🚀 To start processing the queue, run:');
            $this->line('  <fg=cyan>php artisan queue:work redis --queue=oracle-monitor --tries=2 --timeout=3600</>');
            $this->newLine();

            $this->info('📊 To monitor the job status, you can:');
            $this->line('  • View logs: <fg=cyan>tail -f storage/logs/laravel.log</>');
            $this->line('  • Check queue: <fg=cyan>php artisan queue:failed:list</>');
            $this->line('  • Use Horizon: <fg=cyan>php artisan horizon</> (if installed)');
            $this->newLine();

            $this->info('💡 To stop monitoring:');
            $this->line('  • Stop the worker process');
            $this->line('  • The job will not respawn until manually dispatched again');
            $this->newLine();

            Log::info('🔵 MonitorOracleChanges job dispatched to oracle-monitor queue', [
                'command' => 'erp:monitor-oracle',
                'timestamp' => now()->toIso8601String(),
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch job: '.$e->getMessage());

            Log::critical('Failed to dispatch MonitorOracleChanges job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
