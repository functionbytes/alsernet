<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Template\Database\Factories\ShortcodeFactory;

class Shortcode extends Model
{
    use HasFactory;

    protected $table = 'shortcodes';

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'category',
        'config_fields',
        'shortcode_template',
        'render_template',
        'css_code',
        'js_code',
        'sort_order',
        'is_active',
    ];

    protected static function newFactory(): ShortcodeFactory
    {
        return ShortcodeFactory::new();
    }

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
