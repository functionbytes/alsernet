<?php

namespace Modules\Page\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageCacheAudit;
use Modules\Page\Models\PageCacheConfig;

class PageCacheService
{
    private const CACHE_PREFIX = 'pages:';

    private const STATS_PREFIX = 'pages:stats:';

    private const PAGE_TTL_KEY = 'cache.pages_ttl';

    private const PAGES_ENABLED_KEY = 'cache.pages_enabled';

    /**
     * Check if page caching is enabled
     */
    public static function isEnabled(): bool
    {
        return Setting::get(self::PAGES_ENABLED_KEY) === '1';
    }

    /**
     * Get cache TTL in minutes
     */
    public static function getTtlInMinutes(): int
    {
        return (int) Setting::get(self::PAGE_TTL_KEY, 1440);
    }

    /**
     * Get cache TTL in seconds
     */
    public static function getTtlInSeconds(): int
    {
        return self::getTtlInMinutes() * 60;
    }

    /**
     * Get cache key for a page slug
     */
    public static function getCacheKey(string $slug, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return self::CACHE_PREFIX.$locale.':'.$slug;
    }

    /**
     * Get cache stats key
     */
    private static function getStatsKey(string $slug): string
    {
        return self::STATS_PREFIX.$slug;
    }

    /**
     * Get cached page or return null
     */
    public static function get(string $slug): ?array
    {
        if (! self::isEnabled()) {
            return null;
        }

        $key = self::getCacheKey($slug);
        $data = Cache::get($key);

        if ($data) {
            self::recordHit($slug);
        } else {
            self::recordMiss($slug);
        }

        return $data;
    }

    /**
     * Cache a page
     */
    public static function set(Page $page, ?string $locale = null): void
    {
        if (! self::isEnabled()) {
            return;
        }

        // Check if page is in blacklist
        if (self::isBlacklisted($page->id)) {
            return;
        }

        $locale = $locale ?? app()->getLocale();

        $data = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'template' => $page->template,
            'published_at' => $page->published_at,
            'locale' => $locale,
            'cached_at' => now()->toDateTimeString(),
        ];

        $key = self::getCacheKey($page->slug, $locale);

        // Use tags if cache store supports them (Redis), otherwise put directly
        if (self::supportsTagging()) {
            Cache::tags(['pages', 'pages:'.$page->id])->put($key, $data, self::getTtlInSeconds());
        } else {
            Cache::put($key, $data, self::getTtlInSeconds());
        }

        // Record audit
        self::auditLog('cached', $page->id, $slug = $page->slug);

        Log::info('Page cached', ['slug' => $page->slug, 'locale' => $locale]);
    }

    /**
     * Clear cache for a page
     */
    public static function forget(string $slug, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        Cache::forget(self::getCacheKey($slug, $locale));
        self::auditLog('cleared', null, $slug);
    }

    /**
     * Clear cache for a page across all locales
     */
    public static function forgetAllLocales(string $slug): void
    {
        $locales = config('app.supported_locales', [app()->getLocale()]);

        foreach ($locales as $locale) {
            Cache::forget(self::getCacheKey($slug, $locale));
        }

        self::auditLog('cleared_all_locales', null, $slug);
    }

    /**
     * Clear all page cache
     */
    public static function flushAll(): void
    {
        // Use tags if cache store supports them (Redis)
        if (self::supportsTagging()) {
            Cache::tags(['pages'])->flush();
        } else {
            // For file driver, manually delete all page cache keys
            $keys = Cache::getStore()->getPrefix() ? glob(storage_path('framework/cache').'/*') : [];
            foreach ($keys as $key) {
                if (strpos(basename($key), 'pages:') === 0) {
                    @unlink($key);
                }
            }
        }

        self::auditLog('flushed_all', null, null);
        Log::info('All page cache flushed');
    }

    /**
     * Check if cache store supports tagging
     */
    private static function supportsTagging(): bool
    {
        // Only Redis and Memcached support tagging
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'dynamodb']);
    }

    /**
     * Warm cache for a page
     */
    public static function warm(Page $page): void
    {
        $locales = config('app.supported_locales', [app()->getLocale()]);

        foreach ($locales as $locale) {
            self::set($page, $locale);
        }

        self::auditLog('warmed', $page->id, $page->slug);
        Log::info('Page cache warmed', ['id' => $page->id, 'slug' => $page->slug]);
    }

    /**
     * Warm cache for popular pages
     */
    public static function warmPopular(int $limit = 10): int
    {
        $pages = Page::published()
            ->where('is_active', true)
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        foreach ($pages as $page) {
            self::warm($page);
        }

        self::auditLog('warmed_popular', null, null);

        return $pages->count();
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $totalHits = Cache::get(self::STATS_PREFIX.'total_hits', 0);
        $totalMisses = Cache::get(self::STATS_PREFIX.'total_misses', 0);
        $totalRequests = $totalHits + $totalMisses;
        $hitRatio = $totalRequests > 0 ? round(($totalHits / $totalRequests) * 100, 2) : 0;

        // Get size
        $size = self::getCacheSize();

        // Get cached pages count
        $cachedCount = self::getCachedPagesCount();

        return [
            'enabled' => self::isEnabled(),
            'ttl_minutes' => self::getTtlInMinutes(),
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'total_requests' => $totalRequests,
            'hit_ratio' => $hitRatio,
            'size_bytes' => $size,
            'cached_pages_count' => $cachedCount,
            'last_cleared' => Cache::get(self::STATS_PREFIX.'last_cleared'),
            'last_warmed' => Cache::get(self::STATS_PREFIX.'last_warmed'),
        ];
    }

    /**
     * Get cached pages count
     */
    private static function getCachedPagesCount(): int
    {
        if (Cache::store() === 'redis') {
            $keys = Cache::connection()->keys(self::CACHE_PREFIX.'*');

            return count($keys);
        }

        return 0;
    }

    /**
     * Get cache size in bytes
     */
    private static function getCacheSize(): int
    {
        if (Cache::store() !== 'redis') {
            return 0;
        }

        $size = 0;
        $keys = Cache::connection()->keys(self::CACHE_PREFIX.'*');

        foreach ($keys as $key) {
            $value = Cache::connection()->get($key);
            $size += strlen(serialize($value));
        }

        return $size;
    }

    /**
     * Record cache hit
     */
    private static function recordHit(string $slug): void
    {
        Cache::increment(self::STATS_PREFIX.'total_hits');
        Cache::increment(self::STATS_PREFIX.'hits:'.$slug);
    }

    /**
     * Record cache miss
     */
    private static function recordMiss(string $slug): void
    {
        Cache::increment(self::STATS_PREFIX.'total_misses');
        Cache::increment(self::STATS_PREFIX.'misses:'.$slug);
    }

    /**
     * Check if page is blacklisted from caching
     */
    private static function isBlacklisted(int $pageId): bool
    {
        $config = PageCacheConfig::where('page_id', $pageId)
            ->where('cache_enabled', false)
            ->exists();

        return $config;
    }

    /**
     * Record audit log
     */
    private static function auditLog(string $action, ?int $pageId, ?string $slug): void
    {
        try {
            PageCacheAudit::create([
                'action' => $action,
                'page_id' => $pageId,
                'slug' => $slug,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record cache audit', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear stats
     */
    public static function clearStats(): void
    {
        Cache::forget(self::STATS_PREFIX.'total_hits');
        Cache::forget(self::STATS_PREFIX.'total_misses');
    }
}
