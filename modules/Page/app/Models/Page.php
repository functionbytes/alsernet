<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Page\Database\Factories\PageFactory;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Traits\Versionable;
use Modules\Seo\Services\SchemaOrgService;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasStructuredData;
use Modules\Sitemap\Traits\HasSitemapItems;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use HasFactory, HasSeo, HasSitemapItems, HasStructuredData, InteractsWithMedia, SoftDeletes, Versionable;

    private ?Collection $cachedAncestors = null;

    private ?string $cachedFullSlug = null;

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'content',
        'template',
        'page_type',
        'description',
        'status',
        'user_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_noindex',
        'seo_image_url',
        'header_style',
        'published_at',
        'publish_at',
        'unpublish_at',
        'pending_approval',
        'notify_subscribers',
        'featured_image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => PageStatus::class,
        'seo_noindex' => 'boolean',
        'pending_approval' => 'boolean',
        'notify_subscribers' => 'boolean',
        'published_at' => 'datetime',
        'publish_at' => 'datetime',
        'unpublish_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    // Accessors 'url', 'featured_image', and 'full_slug' are available on-demand
    // but excluded from $appends to avoid DB queries on every serialization.
    // API Resources and controllers that need them must access them explicitly.
    protected $appends = [];

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return PageStatus::options();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns the page.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all preview tokens for the page.
     */
    public function previewTokens(): HasMany
    {
        return $this->hasMany(PagePreviewToken::class);
    }

    /**
     * Get active preview tokens for the page.
     */
    public function activePreviewTokens(): HasMany
    {
        return $this->hasMany(PagePreviewToken::class)
            ->where('expires_at', '>', now());
    }

    /**
     * Get the parent page.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Get the child pages.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    /**
     * Get all performance metrics for the page.
     */
    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(PagePerformanceMetric::class);
    }

    /**
     * Get the latest performance metric for a given strategy.
     */
    public function latestPerformance(string $strategy = 'mobile'): HasOne
    {
        return $this->hasOne(PagePerformanceMetric::class)
            ->where('strategy', $strategy)
            ->latestOfMany();
    }

    /**
     * Get the categories for the page.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PageCategory::class, 'page_category_page');
    }

    /**
     * Get the tags for the page.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PageTag::class, 'page_tag_page');
    }

    /**
     * Get all approval records for the page.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(PageApproval::class);
    }

    /**
     * Get the most recent approval record for the page.
     */
    public function latestApproval(): HasOne
    {
        return $this->hasOne(PageApproval::class)->latestOfMany();
    }

    /**
     * Get all translations for the page.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    /**
     * Get the translation for a specific locale.
     */
    public function translation(string $locale): HasOne
    {
        return $this->hasOne(PageTranslation::class)->forLocale($locale);
    }

    /**
     * Get a translated field value with fallback to the page field.
     */
    public function trans(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->relationLoaded('translations')
            ? $this->translations->first(fn ($t) => ($t->localeModel?->code ?? $t->locale_id) === $locale)
            : $this->translations()->forLocale($locale)->first();

        if ($translation && filled($translation->$field)) {
            return $translation->$field;
        }

        return $this->$field ?? null;
    }

    /**
     * Check if a translation exists for the given locale.
     */
    public function hasTranslation(string $locale): bool
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->contains(fn ($t) => ($t->localeModel?->code ?? $t->locale_id) === $locale);
        }

        return $this->translations()->forLocale($locale)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', PageStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', PageStatus::Draft->value);
    }

    /**
     * Scope a query to only include pending pages.
     */
    public function scopePending($query)
    {
        return $query->where('status', PageStatus::Pending->value);
    }

    /**
     * Scope a query to search pages by title or content.
     */
    public function scopeSearch($query, $search)
    {
        if (! empty($search)) {
            return $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', function ($tq) use ($search) {
                        $tq->where('title', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Scope a query to search pages using FULLTEXT search.
     * Requires FULLTEXT index on title, content, and description columns.
     */
    public function scopeSearchFullText($query, $search)
    {
        if (! empty($search)) {
            // Escape special characters for FULLTEXT search
            $search = trim($search);

            return $query->whereRaw(
                'MATCH(title, content, description) AGAINST(? IN BOOLEAN MODE)',
                [$search.'*']
            );
        }

        return $query;
    }

    /**
     * Combined search method that tries FULLTEXT first, then falls back to LIKE.
     * This provides the best balance of performance and flexibility.
     *
     * @param  string  $term  Search term
     * @param  bool  $useFullText  Whether to use full-text search (default: true)
     * @return Builder
     */
    public static function searchPages($term, $useFullText = true)
    {
        $query = static::query();

        if (empty($term)) {
            return $query;
        }

        if ($useFullText && strlen($term) >= 3) {
            try {
                $query->searchFullText($term);
            } catch (\Exception $e) {
                logger()->warning('Full-text search failed, falling back to LIKE', [
                    'error' => $e->getMessage(),
                    'term' => $term,
                ]);
                $query->search($term);
            }
        } else {
            $query->search($term);
        }

        return $query;
    }

    /**
     * Scope a query to only include scheduled pages (for publishing).
     */
    public function scopeScheduledForPublishing($query)
    {
        return $query->where('status', PageStatus::Draft->value)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now());
    }

    /**
     * Scope a query to only include scheduled pages (for unpublishing).
     */
    public function scopeScheduledForUnpublishing($query)
    {
        return $query->where('status', PageStatus::Published->value)
            ->whereNotNull('unpublish_at')
            ->where('unpublish_at', '<=', now());
    }

    /**
     * Scope a query to only include root pages (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include error pages (pages with a page_type set).
     */
    public function scopeErrorPages(Builder $query): Builder
    {
        return $query->whereNotNull('page_type');
    }

    /**
     * Find a page by its page_type value.
     */
    public static function findByPageType(string $type): ?self
    {
        return static::query()->where('page_type', $type)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the page's URL.
     */
    public function getUrlAttribute(): string
    {
        $prefix = setting('permalink-modules-page-models-page', '');
        $path = $prefix ? "{$prefix}/{$this->slug}" : $this->slug;

        return url("/{$path}");
    }

    /**
     * Get the full hierarchical slug path.
     * Example: /servicios/ventanas-pvc
     *
     * Only traverses pre-loaded parent relations to avoid N+1 queries.
     * Callers that need the full hierarchy must eager-load with('parent.parent.parent').
     */
    public function getFullSlugAttribute(): string
    {
        if ($this->cachedFullSlug !== null) {
            return $this->cachedFullSlug;
        }

        $slugs = [];
        $current = $this;
        $depth = 0;

        while ($current && $depth < 5) {
            array_unshift($slugs, $current->slug ?? '');
            $current = $current->relationLoaded('parent') ? $current->parent : null;
            $depth++;
        }

        $prefix = setting('permalink-modules-page-models-page', '');
        if ($prefix) {
            array_unshift($slugs, $prefix);
        }

        return $this->cachedFullSlug = '/'.implode('/', array_filter($slugs));
    }

    /**
     * Get ancestor pages ordered from root to immediate parent.
     *
     * Only traverses pre-loaded parent relations to avoid N+1 queries.
     * Callers that need the full ancestor chain must eager-load with('parent.parent.parent').
     *
     * @return Collection<int, static>
     */
    public function getAncestorsAttribute(): Collection
    {
        if ($this->cachedAncestors !== null) {
            return $this->cachedAncestors;
        }

        $ancestors = collect();
        $parent = $this->relationLoaded('parent') ? $this->parent : null;
        $depth = 0;

        while ($parent && $depth < 5) {
            $ancestors->prepend($parent);
            $parent = $parent->relationLoaded('parent') ? $parent->parent : null;
            $depth++;
        }

        return $this->cachedAncestors = $ancestors;
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured');

        if ($media) {
            return $media->getUrl();
        }

        return $this->featured_image_url ?: null;
    }

    /**
     * Set the slug attribute automatically from title.
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;

        // Auto-generate slug if not set
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Check if the page is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === PageStatus::Draft;
    }

    /**
     * Check if the page is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PageStatus::Pending;
    }

    /**
     * Publish the page.
     */
    public function publish(): bool
    {
        $this->status = PageStatus::Published;
        $this->published_at = $this->published_at ?? now();

        return $this->save();
    }

    /**
     * Unpublish the page.
     */
    public function unpublish(): bool
    {
        $this->status = PageStatus::Draft;

        return $this->save();
    }

    /**
     * Get excerpt from content.
     */
    public function getExcerpt(int $length = 150): string
    {
        $content = strip_tags($this->content);

        return Str::limit($content, $length);
    }

    /**
     * Check if the page is scheduled for publishing.
     */
    public function isScheduled(): bool
    {
        return $this->publish_at !== null && $this->status === PageStatus::Draft;
    }

    /**
     * Check if the page will be published in the future.
     */
    public function willBePublished(): bool
    {
        return $this->publish_at !== null
            && $this->publish_at->isFuture()
            && $this->status === PageStatus::Draft;
    }

    /**
     * Check if the page will be unpublished in the future.
     */
    public function willBeUnpublished(): bool
    {
        return $this->unpublish_at !== null
            && $this->unpublish_at->isFuture()
            && $this->status === PageStatus::Published;
    }

    public function getSchemaType(): string
    {
        return 'WebPage';
    }

    /**
     * Generate WebPage schema using the active locale translation.
     */
    public function generateSchema(?string $type = null, array $options = []): array
    {
        $locale = app()->getLocale();
        $trans = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : null;

        $title = $trans?->seo_title ?: $trans?->title ?: $this->name;
        $description = $trans?->seo_description ?: $trans?->description ?: '';
        $url = $trans?->slug ? url($trans->slug) : $this->url;
        $image = $trans?->seo_image_url ?: null;

        $data = array_filter(compact('title', 'description', 'url', 'image'));

        return app(SchemaOrgService::class)
            ->generateWebPageSchema($data, $options);
    }

    /**
     * Get breadcrumb items for Schema.org BreadcrumbList.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public function getBreadcrumbItems(): array
    {
        $locale = app()->getLocale();

        $items = [['name' => 'Inicio', 'url' => url('/')]];

        foreach ($this->ancestors as $ancestor) {
            $ancestorTrans = $ancestor->relationLoaded('translations')
                ? $ancestor->translations->firstWhere('locale', $locale)
                : null;
            $items[] = [
                'name' => $ancestorTrans?->title ?? $ancestor->name,
                'url' => $ancestorTrans?->slug ? url($ancestorTrans->slug) : $ancestor->url,
            ];
        }

        $trans = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : null;

        $items[] = [
            'name' => $trans?->title ?? $this->name,
            'url' => $trans?->slug ? url($trans->slug) : $this->url,
        ];

        return $items;
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('seo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Generate a preview token for the page.
     *
     * @param  int  $expiresInHours  Hours until the token expires (default: 24)
     * @param  int|null  $userId  User ID creating the token
     */
    public function generatePreviewToken(int $expiresInHours = 24, ?int $userId = null): PagePreviewToken
    {
        return PagePreviewToken::create([
            'page_id' => $this->id,
            'token' => PagePreviewToken::generateUniqueToken(),
            'expires_at' => now()->addHours($expiresInHours),
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Get the preview URL for the page.
     */
    public function getPreviewUrl(): ?string
    {
        $token = $this->activePreviewTokens()->latest()->first();

        if (! $token) {
            return null;
        }

        return $token->getPreviewUrl();
    }

    /**
     * Check if the page has an active preview token.
     */
    public function hasActivePreviewToken(): bool
    {
        return $this->activePreviewTokens()->exists();
    }

    /**
     * Get or create a preview token for the page.
     *
     * @param  int  $expiresInHours  Hours until the token expires (default: 24)
     * @param  int|null  $userId  User ID creating the token
     */
    public function getOrCreatePreviewToken(int $expiresInHours = 24, ?int $userId = null): PagePreviewToken
    {
        $token = $this->activePreviewTokens()->latest()->first();

        if ($token) {
            return $token;
        }

        return $this->generatePreviewToken($expiresInHours, $userId);
    }

    /**
     * Revoke all active preview tokens for the page.
     *
     * @return int Number of tokens revoked
     */
    public function revokeAllPreviewTokens(): int
    {
        return $this->activePreviewTokens()->update([
            'expires_at' => now(),
        ]);
    }
}
