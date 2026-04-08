<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasSlug;

class PageTag extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug'];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_tag_page');
    }
}
