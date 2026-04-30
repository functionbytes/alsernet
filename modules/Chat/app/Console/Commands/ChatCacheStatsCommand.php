<?php

namespace Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ChatCacheStatsCommand extends Command
{
    protected $signature = 'chat:cache-stats {--detailed}';

    protected $description = 'Display chat cache statistics and Redis memory usage';

    public function handle(): int
    {
        if (config('cache.default') !== 'redis') {
            $this->error('⚠️  Redis cache required for detailed statistics');
            $this->info('Current cache driver: '.config('cache.default'));
            $this->newLine();
            $this->info('To enable Redis cache:');
            $this->info('1. Set CACHE_DRIVER=redis in .env');
            $this->info('2. Run: php artisan config:clear');
            $this->info('3. Test: php artisan tinker → Cache::put("test", "works", 60)');

            return self::FAILURE;
        }

        $this->info('📊 Chat Module Cache Statistics');
        $this->newLine();

        $this->displayRedisMemory();
        $this->newLine();
        $this->displayCacheKeys();

        if ($this->option('detailed')) {
            $this->newLine();
            $this->displayDetailedStats();
        }

        $this->newLine();
        $this->displayRecommendations();

        return self::SUCCESS;
    }

    private function displayRedisMemory(): void
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('memory');

            $this->info('🔴 Redis Memory Usage:');
            $this->table(['Metric', 'Value'], [
                ['Used Memory', $info['used_memory_human'] ?? 'N/A'],
                ['Peak Memory', $info['used_memory_peak_human'] ?? 'N/A'],
                ['Memory Limit', config('database.redis.options.parameters.maxmemory', 'Not set')],
                ['Eviction Policy', $info['maxmemory_policy'] ?? 'Not set'],
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to get Redis memory info: '.$e->getMessage());
        }
    }

    private function displayCacheKeys(): void
    {
        $this->info('🔑 Cache Keys by Prefix:');

        $prefixes = [
            'webhook:customer' => 'Webhook customer lookups',
            'webhook:conversation' => 'Webhook conversation lookups',
            'conversations:stats' => 'Conversation filter counts',
            'chat:inboxes' => 'Inbox metadata',
            'chat:teams' => 'Team metadata',
            'chat:labels' => 'Label metadata',
            'presence:user' => 'User presence tracking',
            'presence:channel' => 'Channel presence tracking',
        ];

        $rows = [];
        foreach ($prefixes as $pattern => $description) {
            $count = $this->countKeys($pattern.':*');
            $example = $this->getExampleKey($pattern.':*');
            $rows[] = [$pattern, $count, $description, $example];
        }

        $this->table(['Prefix', 'Count', 'Description', 'Example Key'], $rows);
    }

    private function displayDetailedStats(): void
    {
        $this->info('📈 Redis Performance Stats:');

        try {
            $redis = Cache::getRedis();
            $stats = $redis->info('stats');

            $hits = (int) ($stats['keyspace_hits'] ?? 0);
            $misses = (int) ($stats['keyspace_misses'] ?? 0);
            $total = $hits + $misses;
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;

            $this->table(['Metric', 'Value'], [
                ['Total Requests', number_format($total)],
                ['Cache Hits', number_format($hits)],
                ['Cache Misses', number_format($misses)],
                ['Hit Rate', $hitRate.'%'],
                ['Evicted Keys', number_format((int) ($stats['evicted_keys'] ?? 0))],
                ['Expired Keys', number_format((int) ($stats['expired_keys'] ?? 0))],
            ]);

            // Color-coded hit rate assessment
            if ($hitRate >= 85) {
                $this->info('✅ Cache hit rate is EXCELLENT (>= 85%)');
            } elseif ($hitRate >= 70) {
                $this->warn('⚠️  Cache hit rate is ACCEPTABLE (70-85%) - Consider optimization');
            } else {
                $this->error('❌ Cache hit rate is LOW (< 70%) - Optimization required!');
            }
        } catch (\Exception $e) {
            $this->error('Failed to get Redis stats: '.$e->getMessage());
        }
    }

    private function displayRecommendations(): void
    {
        $totalKeys = $this->countKeys('*');
        $memoryUsed = $this->getMemoryBytes();

        $this->info('💡 Recommendations:');

        $recommendations = [];

        if ($totalKeys > 10000) {
            $recommendations[] = '⚠️  High key count ('.number_format($totalKeys).'). Consider reducing TTLs or adding cleanup.';
        }

        if ($memoryUsed > 500 * 1024 * 1024) { // 500MB
            $recommendations[] = '⚠️  Memory usage high ('.round($memoryUsed / 1024 / 1024, 1).'MB). Consider cache compression or increasing limits.';
        }

        $webhookCustomerCount = $this->countKeys('webhook:customer:*');
        if ($webhookCustomerCount > 5000) {
            $recommendations[] = '⚠️  Many webhook customer keys ('.number_format($webhookCustomerCount).'). Consider reducing TTL from 5min to 3min.';
        }

        $statsCount = $this->countKeys('conversations:stats:*');
        if ($statsCount > 500) {
            $recommendations[] = 'ℹ️  Stats cache has '.number_format($statsCount).' entries. This is normal for multi-user accounts.';
        }

        if (empty($recommendations)) {
            $recommendations[] = '✅ Cache configuration looks healthy! No optimization needed.';
        }

        foreach ($recommendations as $recommendation) {
            $this->line('  '.$recommendation);
        }

        $this->newLine();
        $this->info('For detailed optimization guide, see: docs/performance/REDIS_CACHING_STRATEGY.md');
    }

    private function countKeys(string $pattern): int
    {
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);

            return is_array($keys) ? count($keys) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getExampleKey(string $pattern): string
    {
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);

            if (is_array($keys) && count($keys) > 0) {
                // Remove cache prefix if present
                $key = $keys[0];
                $prefix = config('cache.prefix');
                if ($prefix && str_starts_with($key, $prefix)) {
                    $key = substr($key, strlen($prefix));
                }

                return $key;
            }

            return 'N/A';
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    private function getMemoryBytes(): int
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('memory');

            return (int) ($info['used_memory'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
