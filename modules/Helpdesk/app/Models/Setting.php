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
    /**
     * Memoria de la PETICIÓN en curso.
     *
     * La caché de Redis ya evitaba el SELECT, pero no la ida y vuelta a Redis:
     * abrir el detalle de un ticket leía siete ajustes y repetía cuatro de
     * ellos dentro del mismo request (medido 7-sep-2026). Un ajuste no puede
     * cambiar a mitad de petición —y si lo hace es porque set() lo ha cambiado,
     * que limpia esto—, así que la segunda lectura no tiene por qué salir del
     * proceso. Se vacía solo al terminar el request.
     *
     * @var array<string, mixed>
     */
    private static array $memo = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$memo[$key] ??= Cache::remember(
            self::cacheKey($key),
            self::CACHE_TTL_SECONDS,
            fn () => static::where('key', $key)->value('value') ?? self::MISSING_SENTINEL,
        );

        return $value === self::MISSING_SENTINEL ? $default : $value;
    }

    /**
     * Olvida lo memoizado. La llama set() y la necesitan los tests, que dentro
     * de un mismo proceso cambian ajustes y vuelven a leerlos.
     */
    public static function forgetMemo(?string $key = null): void
    {
        if ($key === null) {
            self::$memo = [];

            return;
        }

        unset(self::$memo[$key]);
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
        self::forgetMemo($key);
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
