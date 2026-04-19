<?php

namespace Modules\Sitemap\Traits;

use Illuminate\Database\Eloquent\Collection;

trait HasSitemapItems
{
    /**
     * Column used to filter published records.
     * Override in the model: protected static string $sitemapStatusColumn = 'is_active';
     */
    protected static string $sitemapStatusColumn = 'status';

    /**
     * Value that means "published".
     * Override in the model: protected static string $sitemapStatusValue = '1';
     */
    protected static string $sitemapStatusValue = 'published';

    public static function getSitemapItems(): Collection
    {
        return static::query()
            ->where(static::$sitemapStatusColumn, static::$sitemapStatusValue)
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn ($item) => ! $item->excludeFromSitemap())
            ->values();
    }

    public static function chunkSitemapItems(int $chunkSize, callable $callback): void
    {
        static::query()
            ->where(static::$sitemapStatusColumn, static::$sitemapStatusValue)
            ->orderByDesc('updated_at')
            ->chunk($chunkSize, function ($items) use ($callback) {
                foreach ($items->filter(fn ($item) => ! $item->excludeFromSitemap()) as $item) {
                    $callback($item);
                }
            });
    }

    /**
     * Return true to exclude this specific record from the sitemap.
     * Override in the model to add custom logic.
     *
     * Example: public function excludeFromSitemap(): bool { return $this->robots_noindex; }
     */
    public function excludeFromSitemap(): bool
    {
        return false;
    }

    public function getSitemapPriorityAttribute(): string
    {
        return '0.8';
    }

    public function getSitemapChangefreqAttribute(): string
    {
        return 'weekly';
    }

    public function getUrlAttribute(): string
    {
        if (method_exists($this, 'getRouteKey')) {
            return url('/'.$this->getRouteKey());
        }

        return url('/');
    }
}
