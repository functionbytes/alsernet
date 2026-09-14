<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;

class SyncAll extends Command
{
    protected $signature = 'erp:sync-all
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}
        {--skip-prices : Skip price sync}
        {--mark-prices : Mark latest prices as current}';

    protected $description = 'Orchestrate full ERP sync: Categories → Providers → Products → ProviderProducts → Prices';

    public function handle()
    {
        $this->info('🚀 Starting FULL ERP Synchronization Pipeline');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $startTime = microtime(true);
        $fullSync = $this->option('full');
        $chunkSize = $this->option('chunk');
        $dryRun = $this->option('dry-run');
        $skipPrices = $this->option('skip-prices');
        $markPrices = $this->option('mark-prices');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        $commands = [
            [
                'name' => 'Categories',
                'signature' => 'erp:sync-categories',
                'emoji' => '📂',
            ],
            [
                'name' => 'Providers',
                'signature' => 'erp:sync-providers',
                'emoji' => '🏢',
            ],
            [
                'name' => 'Products',
                'signature' => 'erp:sync-products',
                'emoji' => '📦',
            ],
            [
                'name' => 'Provider Products',
                'signature' => 'erp:sync-provider-products',
                'emoji' => '🔗',
            ],
        ];

        if (! $skipPrices) {
            $priceOptions = [];
            if ($markPrices) {
                $priceOptions[] = '--update-current';
            }
            $commands[] = [
                'name' => 'Prices',
                'signature' => 'erp:sync-prices',
                'emoji' => '💰',
                'options' => $priceOptions,
            ];
        }

        $failed = [];

        foreach ($commands as $idx => $cmd) {
            $stepNum = $idx + 1;
            $total = count($commands);

            $this->newLine();
            $this->info("Step {$stepNum}/{$total}: {$cmd['emoji']} Syncing {$cmd['name']}...");
            $this->line('─────────────────────────────────────────────────');

            $options = [
                '--no-interaction' => true,
            ];

            if ($fullSync) {
                $options['--full'] = true;
            }

            $options['--chunk'] = $chunkSize;

            if ($dryRun) {
                $options['--dry-run'] = true;
            }

            // Add any command-specific options
            if (isset($cmd['options'])) {
                foreach ($cmd['options'] as $option) {
                    $options[$option] = true;
                }
            }

            $exitCode = $this->call($cmd['signature'], $options);

            if ($exitCode !== 0) {
                $failed[] = $cmd['name'];
                $this->error("{$cmd['emoji']} {$cmd['name']} sync FAILED!");
            } else {
                $this->line("{$cmd['emoji']} {$cmd['name']} sync completed successfully!");
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('🎉 FULL SYNC PIPELINE COMPLETED');
        $this->info('═══════════════════════════════════════════════════');
        $this->line("⏱️  Total time: {$duration}s");
        $this->newLine();

        if (empty($failed)) {
            $this->info('✅ All syncs completed successfully!');

            return Command::SUCCESS;
        } else {
            $this->error('⚠️  Some syncs failed:');
            foreach ($failed as $failedCmd) {
                $this->line("  ❌ {$failedCmd}");
            }
            $this->newLine();
            $this->warn('Check logs for details: storage/logs/laravel.log');

            return Command::FAILURE;
        }
    }
}
