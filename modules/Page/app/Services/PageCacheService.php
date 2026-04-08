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

    private const CACHED_COUNT_KEY = 'pages:stats:cached_count';

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

        // Cargar traducciones si no están en memoria
        if (! $page->relationLoaded('translations')) {
            $page->load('translations.localeModel');
        }

        $translation = $page->translations->firstWhere('locale', $locale);

        $data = [
            // Campos no traducibles (siempre del modelo base)
            'id' => $page->id,
            'template' => $page->template,
            'header_style' => $page->header_style,
            'featured_image' => $page->featured_image,
            'status' => $page->status,
            'publish_at' => $page->publish_at,
            'unpublish_at' => $page->unpublish_at,
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
            'locale' => $locale,
            'cached_at' => now()->toDateTimeString(),

            // Campos traducibles: traducción > fallback modelo
            'title' => $translation?->title ?? $page->title,
            'slug' => $translation?->slug ?? $page->slug,
            'content' => $translation?->content ?? $page->content,
            'description' => $translation?->description ?? $page->description,
            'published_at' => $translation?->published_at ?? $page->published_at,
            'seo_title' => $translation?->seo_title ?? $page->seo_title,
            'seo_description' => $translation?->seo_description ?? $page->seo_description,
            'seo_keywords' => $translation?->seo_keywords ?? $page->seo_keywords,
            'seo_image_url' => $translation?->seo_image_url ?? $page->seo_image_url,
            'seo_noindex' => $translation?->seo_noindex ?? $page->seo_noindex,
            'structured_data' => $page->generateCompleteSchema(),
        ];

        $key = self::getCacheKey($translation?->slug ?? $page->slug, $locale);

        // Use tags if cache store supports them (Redis), otherwise put directly
        if (self::supportsTagging()) {
            Cache::tags(['pages', 'pages:'.$page->id])->put($key, $data, self::getTtlInSeconds());
        } else {
            Cache::put($key, $data, self::getTtlInSeconds());
        }

        Cache::increment(self::CACHED_COUNT_KEY);

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
        Cache::forget('pages:cache:blacklist');
        self::decrementCachedCount();
        self::auditLog('cleared', null, $slug);
    }

    /**
     * Clear cache for a page across all locales
     */
    public static function forgetAllLocales(string $slug): void
    {
        $locales = PageService::getSupportedLocales();

        foreach ($locales as $locale) {
            Cache::forget(self::getCacheKey($slug, $locale));
        }

        Cache::decrement(self::CACHED_COUNT_KEY, count($locales));
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
            // For non-taggable drivers (file, array), full flush is the only safe option
            // since Laravel hashes cache keys and partial flush is not supported
            Cache::flush();
        }

        Cache::forget('pages:cache:blacklist');
        Cache::put(self::CACHED_COUNT_KEY, 0);

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
        $locales = PageService::getSupportedLocales();
        $page->load('translations');  // un solo query

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

        return [
            'enabled' => self::isEnabled(),
            'ttl_minutes' => self::getTtlInMinutes(),
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'total_requests' => $totalRequests,
            'hit_ratio' => $hitRatio,
            // cached_pages_count is an approximation: TTL-expired entries are not decremented
            'cached_pages_count' => self::getCachedPagesCount(),
            'last_cleared' => Cache::get(self::STATS_PREFIX.'last_cleared'),
            'last_warmed' => Cache::get(self::STATS_PREFIX.'last_warmed'),
        ];
    }

    /**
     * Get cached pages count from dedicated Redis counter.
     *
     * Using a counter instead of Redis key scanning because pages are stored
     * under tag-namespaced keys that do not match the plain `pages:*` pattern.
     */
    private static function getCachedPagesCount(): int
    {
        if (! self::isEnabled()) {
            return 0;
        }

        return max(0, (int) Cache::get(self::CACHED_COUNT_KEY, 0));
    }

    /**
     * Decrement cached count, clamping at zero to avoid negative values.
     */
    private static function decrementCachedCount(): void
    {
        $current = (int) Cache::get(self::CACHED_COUNT_KEY, 0);

        if ($current > 0) {
            Cache::decrement(self::CACHED_COUNT_KEY);
        }
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
     * Check if page is blacklisted from caching.
     *
     * The full blacklist is fetched once per 5 minutes and cached in Redis,
     * avoiding one DB query per locale per page during warmCache().
     */
    private static function isBlacklisted(int $pageId): bool
    {
        $blacklisted = Cache::remember('pages:cache:blacklist', 300, fn () => PageCacheConfig::where('cache_enabled', false)->pluck('page_id')->toArray()
        );

        return in_array($pageId, $blacklisted, true);
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
