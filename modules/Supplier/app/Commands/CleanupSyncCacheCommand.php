<?php

namespace Modules\Supplier\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CleanupSyncCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:cleanup-sync-cache
                            {--dry-run : Run without actually cleaning cache}
                            {--ttl=60 : Maximum age in minutes for cache flags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup stale sync_in_progress cache flags to prevent permanent blocks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Starting sync cache cleanup...');

        $dryRun = $this->option('dry-run');
        $maxTtl = (int) $this->option('ttl');

        $this->info("  Max TTL: {$maxTtl} minutes");
        $this->info('  Dry Run: '.($dryRun ? 'Yes' : 'No'));

        $patterns = [
            'sync_in_progress_price_*',
            'sync_in_progress_product_*',
            'sync_in_progress_provider_*',
            'erp_sync_price_*',
            'erp_sync_product_*',
            'erp_sync_provider_*',
        ];

        $totalCleaned = 0;
        $totalScanned = 0;

        foreach ($patterns as $pattern) {
            $this->line("\n  Scanning pattern: {$pattern}");

            $keys = $this->getCacheKeysByPattern($pattern);
            $totalScanned += count($keys);

            $this->info('    Found '.count($keys).' keys');

            foreach ($keys as $key) {
                // Check if key is stale (older than max TTL)
                if ($this->isCacheKeyStale($key, $maxTtl)) {
                    if (! $dryRun) {
                        Cache::forget($key);
                    }

                    $totalCleaned++;
                    $this->warn("    ✗ Cleaned: {$key}");
                } else {
                    $this->comment("    ✓ Active: {$key}");
                }
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("✅ Dry run completed: Would clean {$totalCleaned} / {$totalScanned} cache keys");
        } else {
            $this->info("✅ Cleanup completed: Cleaned {$totalCleaned} / {$totalScanned} cache keys");

            Log::info('Sync cache cleanup completed', [
                'total_scanned' => $totalScanned,
                'total_cleaned' => $totalCleaned,
                'max_ttl_minutes' => $maxTtl,
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Get all cache keys matching a pattern
     */
    protected function getCacheKeysByPattern(string $pattern): array
    {
        $driver = Cache::getStore();

        // Redis implementation
        if (method_exists($driver, 'connection')) {
            try {
                $redis = $driver->connection();
                $prefix = Cache::getPrefix();
                $fullPattern = $prefix.$pattern;

                return array_map(
                    fn ($key) => str_replace($prefix, '', $key),
                    $redis->keys($fullPattern) ?: []
                );
            } catch (\Exception $e) {
                $this->error("  Failed to scan Redis keys: {$e->getMessage()}");

                return [];
            }
        }

        // Array/File/Database drivers - not supported
        $this->warn('  Cache driver does not support key scanning. Using fallback (may be incomplete).');

        return $this->getFallbackCacheKeys($pattern);
    }

    /**
     * Fallback method for non-Redis cache drivers
     */
    protected function getFallbackCacheKeys(string $pattern): array
    {
        // For non-Redis drivers, we'll try to construct known keys
        // This is limited but better than nothing

        $keys = [];

        // Try common ID ranges (1-10000)
        if (preg_match('/sync_in_progress_(price|product|provider)_\*/', $pattern, $matches)) {
            $type = $matches[1];

            for ($id = 1; $id <= 10000; $id++) {
                $key = "sync_in_progress_{$type}_{$id}";
                if (Cache::has($key)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /**
     * Check if a cache key is stale
     */
    protected function isCacheKeyStale(string $key, int $maxTtlMinutes): bool
    {
        $value = Cache::get($key);

        // If key doesn't exist or has no value, consider it stale
        if ($value === null) {
            return true;
        }

        // If value is a timestamp, check age
        if (is_numeric($value)) {
            $ageMinutes = (time() - (int) $value) / 60;

            return $ageMinutes > $maxTtlMinutes;
        }

        // If value is boolean true, check for TTL
        // Since Laravel cache doesn't expose TTL directly, we'll consider it stale if > max TTL
        // This is a conservative approach - better to clean than to leave stale flags

        // For now, we'll assume flags older than max TTL should be cleaned
        // This requires implementing a tracking mechanism or accepting some false positives

        // Conservative: Don't clean boolean flags without timestamp
        return false;
    }

    /**
     * Get statistics about cache keys
     */
    public function getStats(): array
    {
        $patterns = [
            'sync_in_progress_price_*',
            'sync_in_progress_product_*',
            'sync_in_progress_provider_*',
        ];

        $stats = [
            'total_keys' => 0,
            'by_pattern' => [],
        ];

        foreach ($patterns as $pattern) {
            $keys = $this->getCacheKeysByPattern($pattern);
            $count = count($keys);

            $stats['total_keys'] += $count;
            $stats['by_pattern'][$pattern] = $count;
        }

        return $stats;
    }
}
