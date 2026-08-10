<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_settings';

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Ajustes prácticamente estáticos (solo cambian cuando un admin guarda un
     * formulario de configuración) pero leídos con `get()` en el camino
     * crítico de decenas de módulos satélite — un mensaje saliente con
     * auto-traducción activa, por ejemplo, hacía 7-8 SELECTs a esta tabla en
     * el mismo request sin ningún beneficio de frescura. TTL corto (5 min)
     * como red de seguridad además de la invalidación explícita en set().
     */
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember(
            self::cacheKey($key),
            self::CACHE_TTL_SECONDS,
            fn () => static::where('key', $key)->value('value') ?? self::MISSING_SENTINEL,
        );

        return $value === self::MISSING_SENTINEL ? $default : $value;
    }

    /**
     * Sentinel distinguible de un valor real guardado como null/''/false —
     * Cache::remember no puede diferenciar "no cacheado" de "cacheado como
     * null" si se cachea null directamente, así que se cachea este marcador
     * en su lugar cuando la clave no existe en BD.
     */
    private const MISSING_SENTINEL = '__helpdesk_setting_missing__';

    /**
     * Set (upsert) a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'helpdesk:setting:'.$key;
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
