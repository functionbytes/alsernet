<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasSlug;

class PageCategory extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'color', 'icon', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_category_page');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
