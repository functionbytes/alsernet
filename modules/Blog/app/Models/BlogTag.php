<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Blog\Database\Factories\BlogTagFactory;

class BlogTag extends Model
{
    use HasFactory;

    protected $table = 'blog_tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'user_id',
    ];

    protected static function newFactory(): BlogTagFactory
    {
        return BlogTagFactory::new();
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tags', 'tag_id', 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogTagTranslation::class, 'blog_tag_id');
    }

    public function translation(string $locale): ?BlogTagTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
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
        return $query->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getUrlAttribute(): string
    {
        return url('/blog/tag/'.$this->slug);
    }
}
