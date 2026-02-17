<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Page\Traits\Versionable;
use Modules\Seo\Traits\HasSeo;
use Modules\Seo\Traits\HasSitemapItems;
use Modules\Seo\Traits\HasStructuredData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use HasFactory, HasSeo, HasSitemapItems, HasStructuredData, InteractsWithMedia, SoftDeletes, Versionable;

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
        'title',
        'slug',
        'content',
        'template',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'string',
        'seo_noindex' => 'boolean',
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
    protected $appends = ['url', 'featured_image'];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_PENDING = 'pending';

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_PENDING => 'Pending Review',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns the page.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all preview tokens for the page.
     */
    public function previewTokens()
    {
        return $this->hasMany(PagePreviewToken::class);
    }

    /**
     * Get active preview tokens for the page.
     */
    public function activePreviewTokens()
    {
        return $this->hasMany(PagePreviewToken::class)
            ->where('expires_at', '>', now());
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
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope a query to only include pending pages.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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
                    ->orWhere('description', 'like', "%{$search}%");
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function searchPages($term, $useFullText = true)
    {
        $query = static::query();

        if (empty($term)) {
            return $query;
        }

        // Try FULLTEXT search first for better performance
        if ($useFullText && strlen($term) >= 3) {
            try {
                $query->searchFullText($term);
            } catch (\Exception $e) {
                // Fall back to LIKE search if FULLTEXT fails
                $query->search($term);
            }
        } else {
            // Use LIKE search for short terms or when FULLTEXT is disabled
            $query->search($term);
        }

        return $query;
    }

    /**
     * Scope a query to only include scheduled pages (for publishing).
     */
    public function scopeScheduledForPublishing($query)
    {
        return $query->where('status', self::STATUS_DRAFT)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now());
    }

    /**
     * Scope a query to only include scheduled pages (for unpublishing).
     */
    public function scopeScheduledForUnpublishing($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('unpublish_at')
            ->where('unpublish_at', '<=', now());
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
     * Get the featured image URL.
     */
    public function getFeaturedImageAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured');

        return $media ? $media->getUrl() : null;
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
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Check if the page is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the page is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Publish the page.
     */
    public function publish(): bool
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_at = $this->published_at ?? now();

        return $this->save();
    }

    /**
     * Unpublish the page.
     */
    public function unpublish(): bool
    {
        $this->status = self::STATUS_DRAFT;

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
        return $this->publish_at !== null && $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the page will be published in the future.
     */
    public function willBePublished(): bool
    {
        return $this->publish_at !== null
            && $this->publish_at->isFuture()
            && $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the page will be unpublished in the future.
     */
    public function willBeUnpublished(): bool
    {
        return $this->unpublish_at !== null
            && $this->unpublish_at->isFuture()
            && $this->status === self::STATUS_PUBLISHED;
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
