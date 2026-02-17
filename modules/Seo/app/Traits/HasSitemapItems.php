<?php

namespace Modules\Seo\Traits;

trait HasSitemapItems
{
    /**
     * Get items for sitemap
     */
    public static function getSitemapItems()
    {
        return static::where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get sitemap priority attribute
     */
    public function getSitemapPriorityAttribute(): string
    {
        return '0.8';
    }

    /**
     * Get sitemap change frequency attribute
     */
    public function getSitemapChangefreqAttribute(): string
    {
        return 'weekly';
    }

    /**
     * Get URL attribute for sitemap
     * Override this method in your model to provide custom URLs
     */
    public function getUrlAttribute(): string
    {
        if (method_exists($this, 'getRouteKey')) {
            return url('/'.$this->getRouteKey());
        }

        return url('/');
    }
}
