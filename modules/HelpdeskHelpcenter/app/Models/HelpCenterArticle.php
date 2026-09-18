<?php

namespace Modules\HelpdeskHelpcenter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Database\Factories\HelpCenterArticleFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HelpCenterArticle extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected static function newFactory(): HelpCenterArticleFactory
    {
        return HelpCenterArticleFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_articles';

    /**
     * Legacy fields kept for backwards compatibility: content, order, excerpt, active, category_id.
     * Canonical equivalents: body (content), position (order), description (excerpt).
     * Category associations use the helpdesk_helpcenter_category_article pivot via categories().
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'body',
        'content',
        'description',
        'excerpt',
        'position',
        'order',
        'views_count',
        'active',
        'draft',
        'hide_from_structure',
        'is_published',
        'author_id',
        'published_at',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_published' => 'boolean',
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'views_count' => 'integer',
            'order' => 'integer',
            'position' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterCategory::class, 'helpdesk_helpcenter_category_article', 'article_id', 'category_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('helpdesk_helpcenter_category_article.position');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterTag::class, 'helpdesk_helpcenter_article_tag', 'article_id', 'tag_id')
            ->withTimestamps();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HelpCenterArticleTranslation::class, 'article_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(HelpCenterArticleVote::class, 'article_id');
    }

    public function translation(string $locale): ?HelpCenterArticleTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }

    /** @param Builder<HelpCenterArticle> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** @param Builder<HelpCenterArticle> $query */
    public function scopeInLocale(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', fn (Builder $q) => $q->where('locale', $locale)->where('is_published', true));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->useFallbackUrl('/theme/images/default-article.png')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(200)
                    ->sharpen(10);

                $this->addMediaConversion('preview')
                    ->width(800)
                    ->height(600)
                    ->sharpen(10);
            });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = static::generateSlug($article->title);
            }
        });

        static::saving(function (HelpCenterArticle $article) {
            $publishedDirty = $article->isDirty('is_published');
            $draftDirty = $article->isDirty('draft');

            if ($publishedDirty && ! $draftDirty) {
                $article->draft = ! $article->is_published;
            } elseif ($draftDirty && ! $publishedDirty) {
                $article->is_published = ! $article->draft;
            }
        });
    }
}
