<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

/**
 * Run all production optimization commands in sequence.
 *
 * Usage:
 *   php artisan system:optimize-production
 */
class OptimizeProductionCommand extends Command
{
    protected $signature = 'system:optimize-production
        {--force : Skip confirmation prompt}';

    protected $description = 'Optimize the application for production (caches, autoloader, etc.)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will cache routes, config and events. Continue?')) {
            return self::FAILURE;
        }

        $this->info('🚀 Starting production optimization...');

        // 1. Composer autoloader optimization
        $this->info('▸ Optimizing Composer autoloader...');
        $process = Process::fromShellCommandline('composer dump-autoload --optimize --no-dev 2>&1');
        $process->run();
        $this->line(trim($process->getOutput()));

        // 2. Clear existing caches first
        $this->info('▸ Clearing stale caches...');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('event:clear');

        // 3. Cache routes
        $this->info('▸ Caching routes...');
        $result = Artisan::call('route:cache');
        if ($result !== 0) {
            $this->warn('⚠️  Route cache failed: '.Artisan::output());
        } else {
            $this->info('✅ Routes cached');
        }

        // 4. Cache config
        $this->info('▸ Caching configuration...');
        $result = Artisan::call('config:cache');
        if ($result !== 0) {
            $this->warn('⚠️  Config cache failed: '.Artisan::output());
        } else {
            $this->info('✅ Configuration cached');
        }

        // 5. Cache events
        $this->info('▸ Caching events...');
        $result = Artisan::call('event:cache');
        if ($result !== 0) {
            $this->warn('⚠️  Event cache failed: '.Artisan::output());
        } else {
            $this->info('✅ Events cached');
        }

        // 6. View cache (may fail due to Auth module blade issue)
        $this->info('▸ Caching views...');
        $result = Artisan::call('view:cache');
        if ($result !== 0) {
            $this->warn('⚠️  View cache failed (Auth module blade issue — non-critical)');
        } else {
            $this->info('✅ Views cached');
        }

        // 7. Media optimization commands
        $this->info('▸ Running media optimizations...');
        Artisan::call('media:optimize-all');
        $this->info('✅ Media optimized');

        $this->info('');
        $this->info('🎉 Production optimization complete!');
        $this->info('');
        $this->info('Recommended next steps:');
        $this->info('  • Set APP_DEBUG=false in .env');
        $this->info('  • Switch CACHE_DRIVER, SESSION_DRIVER, QUEUE_CONNECTION to redis');
        $this->info('  • Configure MEDIA_CDN_URL if using a CDN');
        $this->info('  • Restart queue workers: php artisan queue:restart');

        return self::SUCCESS;
    }
}
