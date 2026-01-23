<?php

namespace Modules\HelpdeskChat\Models\Helpcenters;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HelpcenterArticle extends Model
{
    use SoftDeletes;

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

    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'views' => 'integer',
            'was_helpful' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Categories relationship (many-to-many with position)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(HelpcenterCategory::class, 'helpdesk_helpcenter_category_article', 'article_id', 'category_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('helpdesk_helpcenter_category_article.position');
    }

    /**
     * Author relationship
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Tags relationship
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(HelpcenterTag::class, 'helpdesk_helpcenter_article_tag', 'article_id', 'tag_id')
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
     * Boot method to auto-generate slug
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = static::generateSlug($article->title);
            }
        });
    }
}
