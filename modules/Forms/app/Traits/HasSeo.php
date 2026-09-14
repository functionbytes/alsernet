<?php

namespace Modules\Forms\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Forms\Models\FormSeoMeta;

/**
 * Accessors SEO para un modelo del módulo Forms.
 *
 * Port local del trait homónimo del módulo Seo del proyecto de origen, que aquí
 * no existe: mismos nombres de accessor (seo_title, og_*, twitter_*, robots...)
 * para que las vistas portadas funcionen sin tocarlas, pero apoyado en
 * [[FormSeoMeta]] (tabla propia del módulo) en vez de en `seo_metas`.
 *
 * IMPORTANTE: al usar estos accessors sobre una colección hay que precargar la
 * relación (->with('seoMeta')) para evitar N+1. Los accessors comprueban que la
 * relación esté cargada, así que un eager-load olvidado degrada al fallback en
 * silencio en vez de disparar una consulta por fila.
 */
trait HasSeo
{
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(FormSeoMeta::class, 'seoable');
    }

    protected function loadedSeoMeta(): ?FormSeoMeta
    {
        return $this->relationLoaded('seoMeta') ? $this->getRelation('seoMeta') : null;
    }

    public function getSeoTitleAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->title ?? $this->title ?? null;
    }

    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->description ?? $this->description ?? null;
    }

    public function getSeoKeywordsAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->keywords ?? null;
    }

    public function getOgTitleAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->og_title ?? $meta?->title ?? $this->title ?? null;
    }

    public function getOgDescriptionAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->og_description ?? $meta?->description ?? $this->description ?? null;
    }

    public function getOgImageAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->og_image ?? config('forms.default_og_image');
    }

    public function getOgTypeAttribute(): string
    {
        return $this->loadedSeoMeta()?->og_type ?? 'website';
    }

    public function getTwitterCardAttribute(): string
    {
        return $this->loadedSeoMeta()?->twitter_card ?? 'summary';
    }

    public function getTwitterTitleAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_title ?? $meta?->title ?? $this->title ?? null;
    }

    public function getTwitterDescriptionAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_description ?? $meta?->description ?? $this->description ?? null;
    }

    public function getTwitterImageAttribute(): ?string
    {
        $meta = $this->loadedSeoMeta();

        return $meta?->twitter_image ?? $meta?->og_image ?? config('forms.default_og_image');
    }

    public function getCanonicalUrlAttribute(): ?string
    {
        return $this->loadedSeoMeta()?->canonical_url ?? null;
    }

    public function getRobotsAttribute(): string
    {
        return $this->loadedSeoMeta()?->robots ?? 'index,follow';
    }

    public function seoMetaForLocale(?string $locale = null): ?FormSeoMeta
    {
        return $this->morphOne(FormSeoMeta::class, 'seoable')
            ->where('locale', $locale)
            ->first();
    }

    public function updateSeoMeta(array $data, ?string $locale = null): FormSeoMeta
    {
        return $this->seoMeta()->updateOrCreate(
            [
                'seoable_id' => $this->id,
                'seoable_type' => static::class,
                'locale' => $locale,
            ],
            $data
        );
    }

    public function deleteSeoMeta(): ?bool
    {
        return $this->seoMeta()?->delete();
    }

    public function hasSeoMeta(): bool
    {
        return $this->seoMeta()->exists();
    }

    public function isIndexable(): bool
    {
        return str_contains(strtolower($this->robots), 'index');
    }

    public function isFollowable(): bool
    {
        return str_contains(strtolower($this->robots), 'follow');
    }
}
