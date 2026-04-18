<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FormFieldTypeSetting extends Model
{
    protected $table = 'form_field_type_settings';

    protected $fillable = [
        'type',
        'group_name',
        'group_order',
        'label',
        'icon',
        'sort_order',
        'is_enabled',
        'default_css_class',
        'default_placeholder',
        'default_settings',
        'custom_css',
        'custom_html',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'default_settings' => 'array',
            'group_order' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('group_order')->orderBy('sort_order');
    }

    /**
     * Returns all settings grouped by group_name, ordered by group_order then sort_order.
     *
     * @return Collection<string, Collection<int, static>>
     */
    public static function allGrouped(): Collection
    {
        return static::query()->ordered()->get()->groupBy('group_name');
    }
}
