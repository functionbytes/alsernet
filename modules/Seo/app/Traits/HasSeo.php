<?php

namespace Modules\Seo\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Seo\Models\SeoMeta;

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
     * Get the SEO title (with fallback to model's title).
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->seoMeta?->title ?? $this->title ?? null;
    }

    /**
     * Get the SEO description (with fallback to model's description).
     */
    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->seoMeta?->description ?? $this->description ?? null;
    }

    /**
     * Get the SEO keywords.
     */
    public function getSeoKeywordsAttribute(): ?string
    {
        return $this->seoMeta?->keywords ?? null;
    }

    /**
     * Get the Open Graph title (with fallbacks).
     */
    public function getOgTitleAttribute(): ?string
    {
        return $this->seoMeta?->og_title ?? $this->seoMeta?->title ?? $this->title ?? null;
    }

    /**
     * Get the Open Graph description (with fallbacks).
     */
    public function getOgDescriptionAttribute(): ?string
    {
        return $this->seoMeta?->og_description ?? $this->seoMeta?->description ?? $this->description ?? null;
    }

    /**
     * Get the Open Graph image.
     */
    public function getOgImageAttribute(): ?string
    {
        return $this->seoMeta?->og_image ?? config('Seo.default_og_image');
    }

    /**
     * Get the Open Graph type.
     */
    public function getOgTypeAttribute(): string
    {
        return $this->seoMeta?->og_type ?? 'website';
    }

    /**
     * Get the Twitter card type.
     */
    public function getTwitterCardAttribute(): string
    {
        return $this->seoMeta?->twitter_card ?? 'summary';
    }

    /**
     * Get the Twitter title (with fallbacks).
     */
    public function getTwitterTitleAttribute(): ?string
    {
        return $this->seoMeta?->twitter_title ?? $this->seoMeta?->title ?? $this->title ?? null;
    }

    /**
     * Get the Twitter description (with fallbacks).
     */
    public function getTwitterDescriptionAttribute(): ?string
    {
        return $this->seoMeta?->twitter_description ?? $this->seoMeta?->description ?? $this->description ?? null;
    }

    /**
     * Get the Twitter image.
     */
    public function getTwitterImageAttribute(): ?string
    {
        return $this->seoMeta?->twitter_image ?? $this->seoMeta?->og_image ?? config('Seo.default_og_image');
    }

    /**
     * Get the canonical URL.
     */
    public function getCanonicalUrlAttribute(): ?string
    {
        return $this->seoMeta?->canonical_url ?? null;
    }

    /**
     * Get the robots directive.
     */
    public function getRobotsAttribute(): string
    {
        return $this->seoMeta?->robots ?? 'index,follow';
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
