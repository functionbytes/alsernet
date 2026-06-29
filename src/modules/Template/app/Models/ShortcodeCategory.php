<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortcodeCategory extends Model
{
    protected $table = 'shortcode_categories';

    protected $fillable = [
        'slug',
        'label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function shortcodes(): HasMany
    {
        return $this->hasMany(Shortcode::class, 'category', 'slug');
    }
}
