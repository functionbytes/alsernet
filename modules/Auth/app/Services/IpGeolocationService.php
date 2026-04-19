<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\IpLocation;
use Throwable;

/**
 * Resolve IP addresses to country/city using torann/geoip.
 * Caches results in ip_locations table to avoid repeated lookups.
 */
class IpGeolocationService
{
    /**
     * @return array{country: ?string, city: ?string}
     */
    public function lookup(?string $ip): array
    {
        if (! $ip || $ip === '127.0.0.1' || $ip === '::1') {
            return ['country' => null, 'city' => null];
        }

        $cached = IpLocation::where('ip_address', $ip)->first();

        if ($cached) {
            return [
                'country' => $cached->country_name,
                'city' => $cached->city,
            ];
        }

        try {
            $location = app('geoip')->getLocation($ip);
        } catch (Throwable $e) {
            return ['country' => null, 'city' => null];
        }

        $data = [
            'ip_address' => $ip,
            'country_code' => $location['iso_code'] ?? null,
            'country_name' => $location['country'] ?? null,
            'region_code' => $location['state'] ?? null,
            'region_name' => $location['state_name'] ?? null,
            'city' => $location['city'] ?? null,
            'zipcode' => $location['postal_code'] ?? null,
            'latitude' => $location['lat'] ?? null,
            'longitude' => $location['lon'] ?? null,
        ];

        try {
            IpLocation::create($data);
        } catch (Throwable $e) {
            // Ignore duplicate / race conditions.
        }

        return ['country' => $data['country_name'], 'city' => $data['city']];
    }
}
