<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Database\Factories\BlogPostFactory;
use Modules\Blog\Enums\PostStatus;
use Modules\Seo\Traits\HasStructuredData;

class BlogPost extends Model
{
    use HasFactory, HasStructuredData, SoftDeletes;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'status',
        'is_featured',
        'image',
        'format_type',
        'views',
        'user_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'published_at',
        'publish_at',
        'send_newsletter',
        'newsletter_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_featured' => 'boolean',
            'views' => 'integer',
            'published_at' => 'datetime',
            'publish_at' => 'datetime',
            'send_newsletter' => 'boolean',
            'newsletter_sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BlogPostFactory
    {
        return BlogPostFactory::new();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_post_categories', 'post_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogPostComment::class, 'blog_post_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogPostTranslation::class, 'blog_post_id');
    }

    public function translationLogs(): HasMany
    {
        return $this->hasMany(BlogTranslationLog::class, 'blog_post_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BlogPostVersion::class)->latest('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(BlogPostVersion::class)->latestOfMany('version_number');
    }

    public function translation(string $locale): ?BlogPostTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    public function trans(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $translation = $this->translation($locale);

        if ($translation && filled($translation->$field)) {
            return $translation->$field;
        }

        return $this->$field ?? null;
    }

    public function hasTranslation(string $locale): bool
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->isNotEmpty();
        }

        return $this->translations()->where('locale', $locale)->exists();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function getUrlAttribute(): string
    {
        return url('/blog/'.$this->slug);
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function getExcerptAttribute(): string
    {
        $source = $this->description ?: strip_tags((string) $this->content);

        return mb_substr($source, 0, 200);
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags((string) $this->content));

        return (int) max(1, ceil($wordCount / 200));
    }

    public function relatedPosts(int $limit = 4): Collection
    {
        $relatedIds = collect();

        $categoryIds = $this->relationLoaded('categories')
            ? $this->categories->pluck('id')
            : $this->categories()->pluck('id');

        if ($categoryIds->isNotEmpty()) {
            $ids = static::query()
                ->published()
                ->where('id', '!=', $this->id)
                ->whereHas('categories', fn ($q) => $q->whereIn('blog_categories.id', $categoryIds))
                ->latest('published_at')
                ->limit($limit)
                ->pluck('id');
            $relatedIds = $relatedIds->merge($ids);
        }

        if ($relatedIds->count() < $limit) {
            $tagIds = $this->relationLoaded('tags')
                ? $this->tags->pluck('id')
                : $this->tags()->pluck('id');

            if ($tagIds->isNotEmpty()) {
                $ids = static::query()
                    ->published()
                    ->where('id', '!=', $this->id)
                    ->whereNotIn('id', $relatedIds)
                    ->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $tagIds))
                    ->latest('published_at')
                    ->limit($limit - $relatedIds->count())
                    ->pluck('id');
                $relatedIds = $relatedIds->merge($ids);
            }
        }

        if ($relatedIds->count() < $limit) {
            $ids = static::query()
                ->published()
                ->where('id', '!=', $this->id)
                ->whereNotIn('id', $relatedIds)
                ->latest('published_at')
                ->limit($limit - $relatedIds->count())
                ->pluck('id');
            $relatedIds = $relatedIds->merge($ids);
        }

        return static::query()
            ->whereIn('id', $relatedIds->unique())
            ->with(['categories', 'user'])
            ->get();
    }

    public function scopeMostViewed(Builder $query, int $days = 30): Builder
    {
        return $query
            ->published()
            ->where('published_at', '>=', now()->subDays($days))
            ->orderByDesc('views');
    }

    public function incrementViewCount(): void
    {
        $cacheKey = "blog_post_view:{$this->id}:".request()->ip();

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addHours(24));
        $this->increment('views');
    }

    public function getSchemaType(): string
    {
        return 'BlogPosting';
    }

    public function getSchemaOptions(): array
    {
        return array_filter([
            'author' => $this->user?->name,
        ]);
    }

    public function getBreadcrumbItems(): array
    {
        return [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Blog', 'url' => url('/blog')],
            ['name' => $this->title, 'url' => $this->url],
        ];
    }
}
