<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class HelpCenterTag extends Model
{
    protected $table = 'helpdesk_helpcenter_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Articles relationship
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterArticle::class, 'helpdesk_helpcenter_article_tag', 'tag_id', 'article_id')
            ->withTimestamps();
    }

    /**
     * Generate slug from name
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', $slug.'%')->count();

        if ($count > 0) {
            return $slug.'-'.($count + 1);
        }

        return $slug;
    }

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });
    }

    /**
     * Find or create tag by name
     */
    public static function findOrCreateByName(string $name): self
    {
        $tag = static::where('name', $name)->first();

        if (! $tag) {
            $tag = static::create(['name' => $name]);
        }

        return $tag;
    }
}
