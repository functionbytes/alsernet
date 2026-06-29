<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogTagTranslation extends Model
{
    protected $table = 'blog_tag_translations';

    protected $fillable = [
        'blog_tag_id',
        'locale',
        'name',
        'slug',
        'description',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(BlogTag::class, 'blog_tag_id');
    }
}
