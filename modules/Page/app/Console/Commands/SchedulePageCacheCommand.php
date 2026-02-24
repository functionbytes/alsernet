<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Services\PageCacheService;

class SchedulePageCacheCommand extends Command
{
    protected $signature = 'schedule:page-cache';

    protected $description = 'Scheduled task for page cache warming and maintenance';

    public function handle(): int
    {
        if (!PageCacheService::isEnabled()) {
            $this->info('Page cache is disabled.');
            return self::SUCCESS;
        }

        $this->info('Starting page cache maintenance...');

        // Warm popular pages
        $this->line('Warming popular pages...');
        $count = PageCacheService::warmPopular(20);
        $this->info("Warmed {$count} popular pages.");

        $this->info('Page cache maintenance completed.');
        return self::SUCCESS;
    }
}
