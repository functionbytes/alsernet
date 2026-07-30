<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_settings';

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set (upsert) a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Get all settings for a group as a key => value array.
     * Keys are returned as stored (e.g. "group.key").
     */
    public static function allAsArray(?string $group = null): array
    {
        $query = static::query();

        if ($group) {
            $query->where('group', $group);
        }

        return $query->pluck('value', 'key')->toArray();
    }

    /**
     * Get all settings for a group as a flat key => value array,
     * stripping the "group." prefix so keys match DEFAULTS arrays.
     */
    public static function allAsFlatArray(string $group): array
    {
        $prefix = $group.'.';
        $raw = static::query()->where('group', $group)->pluck('value', 'key')->toArray();

        $flat = [];
        foreach ($raw as $k => $v) {
            $bare = str_starts_with($k, $prefix) ? substr($k, strlen($prefix)) : $k;
            $flat[$bare] = $v;
        }

        return $flat;
    }
}
