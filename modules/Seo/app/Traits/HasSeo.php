<?php

namespace Modules\Seo\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Seo\Models\SeoMeta;

/**
 * Adds SEO meta accessors to a model.
 *
 * IMPORTANT: when using these accessors in a collection/listing, always
 * eager-load the relation (->with('seoMeta')) to avoid N+1 queries. The
 * accessors guard against unloaded relations so missing eager-loads
 * degrade gracefully to fallbacks.
 */
trait HasSeo
{
    /**
     * Get the SEO meta data for this model.
     */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Return the loaded seoMeta relation or null if it wasn't eager-loaded.
     */
    protected function loadedSeoMeta(): ?SeoMeta
    {
        return $this->relationLoaded('seoMeta') ? $this->getRelation('seoMeta') : null;
    }

    /**
     * Get the SEO title (with fallback to model's title).
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->title ?? $this->title ?? null;
    }

    /**
     * Get the SEO description (with fallback to model's description).
     */
    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->description ?? $this->description ?? null;
    }

    /**
     * Get the SEO keywords.
     */
    public function getSeoKeywordsAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->keywords ?? null;
    }

    /**
     * Get the Open Graph title (with fallbacks).
     */
    public function getOgTitleAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->og_title ?? $meta?->title ?? $this->title ?? null;
    }

    /**
     * Get the Open Graph description (with fallbacks).
     */
    public function getOgDescriptionAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->og_description ?? $meta?->description ?? $this->description ?? null;
    }

    /**
     * Get the Open Graph image.
     */
    public function getOgImageAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->og_image ?? config('Seo.default_og_image');
    }

    /**
     * Get the Open Graph type.
     */
    public function getOgTypeAttribute(): string
    {
        return $this->loadedSeoMeta()?->og_type ?? 'website';
    }

    /**
     * Get the Twitter card type.
     */
    public function getTwitterCardAttribute(): string
    {
        return $this->loadedSeoMeta()?->twitter_card ?? 'summary';
    }

    /**
     * Get the Twitter title (with fallbacks).
     */
    public function getTwitterTitleAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_title ?? $meta?->title ?? $this->title ?? null;
    }

    /**
     * Get the Twitter description (with fallbacks).
     */
    public function getTwitterDescriptionAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_description ?? $meta?->description ?? $this->description ?? null;
    }

    /**
     * Get the Twitter image.
     */
    public function getTwitterImageAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_image ?? $meta?->og_image ?? config('Seo.default_og_image');
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonicalUrlAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->canonical_url ?? null;
    }

    /**
     * Get the robots directive.
     */
    public function getRobotsAttribute(): string
    {
        return $this->loadedSeoMeta()?->robots ?? 'index,follow';
    }

    /**
     * Get the SEO meta for a specific locale (or the global/default one when null).
     */
    public function seoMetaForLocale(?string $locale = null): ?SeoMeta
    {
        return $this->morphOne(SeoMeta::class, 'seoable')
            ->where('locale', $locale)
            ->first();
    }

    /**
     * Create or update SEO meta data for this model, scoped by locale.
     */
    public function updateSeoMeta(array $data, ?string $locale = null): SeoMeta
    {
        return $this->seoMeta()->updateOrCreate(
            [
                'seoable_id' => $this->id,
                'seoable_type' => get_class($this),
                'locale' => $locale,
            ],
            $data
        );
    }

    /**
     * Delete SEO meta data for this model.
     */
    public function deleteSeoMeta(): ?bool
    {
        return $this->seoMeta()?->delete();
    }

    /**
     * Check if this model has SEO meta data.
     */
    public function hasSeoMeta(): bool
    {
        return $this->seoMeta()->exists();
    }

    /**
     * Check if the page should be indexed.
     */
    public function isIndexable(): bool
    {
        $robots = $this->robots;

        return str_contains(strtolower($robots), 'index');
    }

    /**
     * Check if the page should be followed.
     */
    public function isFollowable(): bool
    {
        $robots = $this->robots;

        return str_contains(strtolower($robots), 'follow');
    }
}
