<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_metas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent seoable model.
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the title for display (with fallback).
     */
    public function getDisplayTitleAttribute(): ?string
    {
        return $this->title ?? $this->seoable?->title ?? null;
    }

    /**
     * Get the description for display (with fallback).
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description ?? $this->seoable?->description ?? null;
    }

    /**
     * Get the Open Graph title (with fallback).
     */
    public function getOgTitleDisplayAttribute(): ?string
    {
        return $this->og_title ?? $this->title ?? $this->seoable?->title ?? null;
    }

    /**
     * Get the Open Graph description (with fallback).
     */
    public function getOgDescriptionDisplayAttribute(): ?string
    {
        return $this->og_description ?? $this->description ?? $this->seoable?->description ?? null;
    }

    /**
     * Get the Twitter title (with fallback).
     */
    public function getTwitterTitleDisplayAttribute(): ?string
    {
        return $this->twitter_title ?? $this->title ?? $this->seoable?->title ?? null;
    }

    /**
     * Get the Twitter description (with fallback).
     */
    public function getTwitterDescriptionDisplayAttribute(): ?string
    {
        return $this->twitter_description ?? $this->description ?? $this->seoable?->description ?? null;
    }

    /**
     * Scope to filter by seoable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('seoable_type', $type);
    }

    /**
     * Scope to filter by robots directive.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRobots($query, string $robots)
    {
        return $query->where('robots', $robots);
    }

    /**
     * Get short class name from seoable_type for display.
     */
    public function getShortTypeAttribute(): string
    {
        return class_basename($this->seoable_type ?? '');
    }

    /**
     * Check if the page should be indexed.
     */
    public function isIndexable(): bool
    {
        $robots = $this->robots ?? 'index,follow';

        return str_contains(strtolower($robots), 'index');
    }

    /**
     * Check if the page should be followed.
     */
    public function isFollowable(): bool
    {
        $robots = $this->robots ?? 'index,follow';

        return str_contains(strtolower($robots), 'follow');
    }
}
