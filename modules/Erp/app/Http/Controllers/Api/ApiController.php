<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Core\Models\Setting;

/**
 * Controlador base para todos los endpoints API del módulo ERP.
 *
 * Proporciona helpers comunes como cachedResult(), que respeta
 * la configuración oracle_enable_cache para activar o desactivar
 * el caché Redis de forma centralizada.
 */
abstract class ApiController extends Controller
{
    /**
     * Ejecuta un callback con o sin caché según oracle_enable_cache.
     *
     * @return array{data: mixed, cached: bool}
     */
    protected function cachedResult(string $key, \Closure $callback, int $ttl = 3600): array
    {
        $settings = Setting::getErpSettings();
        $useCache = (bool) ($settings['oracle_enable_cache'] ?? true);

        if (! $useCache) {
            return ['data' => $callback(), 'cached' => false];
        }

        $cache = cache();

        // Single-key, atomic miss detection: Cache::add() is atomic on Redis
        // and returns true only for the first caller that beat the race. The
        // previous `has() + remember()` pattern (1) doubled the Redis round
        // trips and (2) let N concurrent misses each fire the (heavy Oracle)
        // callback in parallel — classic cache stampede.
        $sentinel = '__cache_miss_sentinel__';
        if ($cache->add($key, $sentinel, $ttl)) {
            // We claimed the miss — populate the real value
            $data = $callback();
            $cache->put($key, $data, $ttl);

            return ['data' => $data, 'cached' => false];
        }

        $cached = $cache->get($key);
        if ($cached === $sentinel) {
            // Another worker is populating it; wait briefly then re-read once
            // before falling back to running the callback ourselves.
            usleep(150_000); // 150ms
            $cached = $cache->get($key);
            if ($cached === null || $cached === $sentinel) {
                return ['data' => $callback(), 'cached' => false];
            }
        }

        return ['data' => $cached, 'cached' => true];
    }
}
