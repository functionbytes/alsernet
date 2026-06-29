<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

class WarmPageCacheCommand extends Command
{
    protected $signature = 'page:warm-cache
                            {--limit=50 : Max pages to warm}
                            {--force : Re-warm already cached pages}';

    protected $description = 'Pre-warm the page cache for published pages';

    public function handle(): int
    {
        if (! PageCacheService::isEnabled()) {
            $this->warn('Page cache is disabled. Enable it in Settings or set PAGE_CACHE_ENABLED=true in .env');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $this->info('Warming page cache...');

        $pages = Page::published()
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        $warmed = 0;

        foreach ($pages as $page) {
            if (! $force && PageCacheService::get($page->slug) !== null) {
                continue;
            }

            PageCacheService::warm($page);
            $warmed++;
        }

        $this->info("Cache warming complete. Warmed {$warmed} pages.");

        return self::SUCCESS;
    }
}
