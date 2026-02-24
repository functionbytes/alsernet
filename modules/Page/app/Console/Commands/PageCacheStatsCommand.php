<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Services\PageCacheService;

class PageCacheStatsCommand extends Command
{
    protected $signature = 'page:cache-stats';

    protected $description = 'Display page cache statistics';

    public function handle(): int
    {
        $stats = PageCacheService::getStats();

        if (! $stats['enabled']) {
            $this->warn('Page cache is disabled.');
            return self::SUCCESS;
        }

        $this->info('=== Page Cache Statistics ===');
        $this->line('');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Status', $stats['enabled'] ? 'Enabled' : 'Disabled'],
                ['TTL (minutes)', $stats['ttl_minutes']],
                ['Total Requests', $stats['total_requests']],
                ['Cache Hits', $stats['total_hits']],
                ['Cache Misses', $stats['total_misses']],
                ['Hit Ratio', $stats['hit_ratio'].'%'],
                ['Cached Pages', $stats['cached_pages_count']],
                ['Cache Size', $this->formatBytes($stats['size_bytes'])],
                ['Last Cleared', $stats['last_cleared'] ?? 'Never'],
                ['Last Warmed', $stats['last_warmed'] ?? 'Never'],
            ]
        );

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
