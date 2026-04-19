<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Seo\Database\Factories\SeoMetaFactory;

class SeoMeta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_metas';

    /**
     * Use the module's factory namespace.
     */
    protected static function newFactory(): SeoMetaFactory
    {
        return SeoMetaFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'title',
        'title_b',
        'description',
        'description_b',
        'ab_winner',
        'ab_impressions_a',
        'ab_impressions_b',
        'keywords',
        'target_keyword',
        'og_title',
        'og_description',
        'og_image',
        'locale',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'schema_custom',
        'schema_type',
        'seo_score',
        'seo_grade',
        'seo_audited_at',
        'gsc_clicks',
        'gsc_impressions',
        'gsc_position',
        'gsc_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'schema_custom' => 'array',
        'seo_score' => 'integer',
        'seo_audited_at' => 'datetime',
        'gsc_clicks' => 'integer',
        'gsc_impressions' => 'integer',
        'gsc_position' => 'decimal:1',
        'gsc_updated_at' => 'datetime',
        'ab_impressions_a' => 'integer',
        'ab_impressions_b' => 'integer',
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
    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->title ?? $this->seoable?->title ?? null,
        );
    }

    /**
     * Get the description for display (with fallback).
     */
    protected function displayDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->description ?? $this->seoable?->description ?? null,
        );
    }

    /**
     * Get the Open Graph title (with fallback).
     */
    protected function ogTitleDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->og_title ?? $this->title ?? $this->seoable?->title ?? null,
        );
    }

    /**
     * Get the Open Graph description (with fallback).
     */
    protected function ogDescriptionDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->og_description ?? $this->description ?? $this->seoable?->description ?? null,
        );
    }

    /**
     * Get the Twitter title (with fallback).
     */
    protected function twitterTitleDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->twitter_title ?? $this->title ?? $this->seoable?->title ?? null,
        );
    }

    /**
     * Get the Twitter description (with fallback).
     */
    protected function twitterDescriptionDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->twitter_description ?? $this->description ?? $this->seoable?->description ?? null,
        );
    }

    /**
     * Get short class name from seoable_type for display.
     */
    protected function shortType(): Attribute
    {
        return Attribute::make(
            get: fn () => class_basename($this->seoable_type ?? ''),
        );
    }

    /**
     * Scope to order by seo_score.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByScore($query, string $direction = 'asc')
    {
        return $query->orderBy('seo_score', $direction);
    }

    /**
     * Scope to filter by seoable type.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('seoable_type', $type);
    }

    /**
     * Scope to filter by robots directive.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithRobots($query, string $robots)
    {
        return $query->where('robots', $robots);
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

    /**
     * Get the active title for A/B testing.
     * When no winner is set and variant B exists, rotate by serving the less-shown variant.
     */
    public function getActiveTitle(): string
    {
        if ($this->ab_winner === 'b' && $this->title_b) {
            return $this->title_b;
        }

        if (! $this->ab_winner && $this->title_b) {
            return $this->ab_impressions_a <= $this->ab_impressions_b
                ? ($this->title ?? '')
                : ($this->title_b ?? $this->title ?? '');
        }

        return $this->title ?? '';
    }

    /**
     * Get the active description for A/B testing.
     * When no winner is set and variant B exists, rotate by serving the less-shown variant.
     */
    public function getActiveDescription(): string
    {
        if ($this->ab_winner === 'b' && $this->description_b) {
            return $this->description_b;
        }

        if (! $this->ab_winner && $this->description_b) {
            return $this->ab_impressions_a <= $this->ab_impressions_b
                ? ($this->description ?? '')
                : ($this->description_b ?? $this->description ?? '');
        }

        return $this->description ?? '';
    }

    /**
     * Determine which A/B variant is currently active (for impression tracking).
     */
    public function getActiveVariant(): string
    {
        if ($this->ab_winner) {
            return $this->ab_winner;
        }

        return $this->ab_impressions_a <= $this->ab_impressions_b ? 'a' : 'b';
    }

    /**
     * Check if A/B testing is active (variant B is configured).
     */
    public function hasAbTest(): bool
    {
        return (bool) ($this->title_b || $this->description_b);
    }
}
