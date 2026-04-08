<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HelpCenterArticle extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_articles';

    protected $fillable = [
        'title',
        'slug',
        'position',
        'body',
        'description',
        'meta_description',
        'draft',
        'hide_from_structure',
        'views',
        'was_helpful',
        'author_id',
    ];

    protected $casts = [
        'draft' => 'boolean',
        'hide_from_structure' => 'boolean',
        'views' => 'integer',
        'was_helpful' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Categories relationship (many-to-many with position)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterCategory::class, 'helpdesk_helpcenter_category_article', 'article_id', 'category_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('helpdesk_helpcenter_category_article.position');
    }

    /**
     * Author relationship
     * Note: User model is in the default connection, not helpdesk
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')
            ->setConnection(config('database.default'));
    }

    /**
     * Tags relationship
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterTag::class, 'helpdesk_helpcenter_article_tag', 'article_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate slug from title
     */
    public static function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = static::where('slug', 'like', $slug.'%')->count();

        if ($count > 0) {
            return $slug.'-'.($count + 1);
        }

        return $slug;
    }

    /**
     * Register media collections
     */
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

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = static::generateSlug($article->title);
            }
        });
    }
}
