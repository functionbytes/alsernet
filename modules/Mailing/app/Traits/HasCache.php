<?php

namespace Modules\Mailing\Traits;

/**
 * HasCache Trait
 *
 * Provides caching functionality for models.
 */
trait HasCache
{
    /**
     * Get cache key for this model.
     */
    public function getCacheKey(): string
    {
        return sprintf('%s:%s', static::class, $this->getKey());
    }

    /**
     * Clear cache for this model.
     */
    public function clearCache(): void
    {
        if (app()->has('cache')) {
            app('cache')->forget($this->getCacheKey());
        }
    }
}
