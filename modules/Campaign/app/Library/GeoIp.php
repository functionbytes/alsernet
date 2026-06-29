<?php

namespace Modules\Campaign\Library;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolución IP → país sin dependencias extra.
 *
 * Estrategia:
 *   1. Si el módulo Locations tiene un servicio, usarlo.
 *   2. Cache local de 7 días por IP.
 *   3. Fallback a https://ip-api.com/json/{ip} (45 req/min gratis, sin API key).
 *
 * Devuelve código ISO 3166-1 alpha-2 ('ES', 'US', etc.) o null si falla.
 */
class GeoIp
{
    public static function country(?string $ip): ?string
    {
        if (empty($ip) || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        // No queremos resolver IPs privadas/loopback
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        return Cache::remember("geoip:{$ip}", now()->addDays(7), fn () => self::resolve($ip));
    }

    protected static function resolve(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,countryCode',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? null) !== 'success') {
                return null;
            }

            return strtoupper((string) $data['countryCode']) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
