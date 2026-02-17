<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Shortcode extends Model
{
    protected $table = 'shortcodes';

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'config_fields',
        'shortcode_template',
        'render_template',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
