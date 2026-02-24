<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Services\PageCacheService;

class PageCacheClearCommand extends Command
{
    protected $signature = 'page:cache-clear {--stats : Also clear statistics}';

    protected $description = 'Clear all page cache';

    public function handle(): int
    {
        PageCacheService::flushAll();
        $this->info('Page cache cleared successfully.');

        if ($this->option('stats')) {
            PageCacheService::clearStats();
            $this->info('Page cache statistics cleared.');
        }

        return self::SUCCESS;
    }
}
