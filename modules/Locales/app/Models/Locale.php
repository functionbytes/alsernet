<?php

namespace Modules\Locales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Locale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'is_default',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /** Scope: active locales ordered by display order */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    /** Scope: all locales ordered by display order then name */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /** Get the default locale, or null if none is set */
    public static function getDefault(): ?static
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get codes of all active locales.
     *
     * @return array<string>
     */
    public static function getActiveCodes(): array
    {
        return static::active()->pluck('code')->toArray();
    }
}
