<?php

namespace Modules\Modules\Models;

use Illuminate\Database\Eloquent\Model;

class NavItemSetting extends Model
{
    protected $table = 'module_nav_settings';

    protected $fillable = ['key', 'enabled'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * Enable or disable a nav item (mini-nav module, sidebar, or sidebar item).
     */
    public static function setEnabled(string $key, bool $enabled): void
    {
        static::updateOrCreate(['key' => $key], ['enabled' => $enabled]);
    }

    /**
     * Get all stored flags as a key => enabled array.
     */
    public static function allAsArray(): array
    {
        return static::query()->pluck('enabled', 'key')->all();
    }
}
