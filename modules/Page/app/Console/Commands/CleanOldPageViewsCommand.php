<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Models\PageView;

class CleanOldPageViewsCommand extends Command
{
    protected $signature = 'page:clean-views {--days= : Days to retain (default from config)}';

    protected $description = 'Delete page_views records older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('page.analytics.retention_days', 90));
        $cutoff = now()->subDays($days);

        $deleted = PageView::query()
            ->where('viewed_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} page_view records older than {$days} days.");

        return self::SUCCESS;
    }
}
