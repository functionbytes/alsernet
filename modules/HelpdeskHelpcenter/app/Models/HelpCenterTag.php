<?php

namespace Modules\HelpdeskHelpcenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class HelpCenterTag extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterArticle::class, 'helpdesk_helpcenter_article_tag', 'tag_id', 'article_id')
            ->withTimestamps();
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public static function findOrCreateByName(string $name): self
    {
        return static::where('name', $name)->first() ?? static::create(['name' => $name]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });
    }
}
