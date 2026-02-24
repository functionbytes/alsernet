<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

class PageCacheWarmCommand extends Command
{
    protected $signature = 'page:cache-warm {--limit=10 : Number of popular pages to warm} {--all : Warm all published pages}';

    protected $description = 'Warm the page cache';

    public function handle(): int
    {
        if (! PageCacheService::isEnabled()) {
            $this->error('Page cache is not enabled.');
            return self::FAILURE;
        }

        if ($this->option('all')) {
            $pages = Page::published()->get();
            $count = 0;

            foreach ($pages as $page) {
                PageCacheService::warm($page);
                $count++;
            }

            $this->info("Warmed cache for {$count} pages.");
        } else {
            $limit = $this->option('limit');
            $count = PageCacheService::warmPopular($limit);
            $this->info("Warmed cache for {$count} popular pages.");
        }

        return self::SUCCESS;
    }
}
