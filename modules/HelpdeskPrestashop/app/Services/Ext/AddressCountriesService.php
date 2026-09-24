<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Países activos de PrestaShop (acción address.countries del bridge) para el
 * selector de país del formulario de dirección. Misma política de caché que
 * getCountryStates(): solo se cachea una respuesta no vacía, así un fallo
 * puntual del bridge no deja el selector vacío durante una hora.
 */
class AddressCountriesService
{
    private const CACHE_KEY = 'ps.ext.address.countries';

    public function __construct(
        private readonly PrestashopContextService $ps
    ) {}

    /**
     * Devuelve null si el bridge no responde o no hay países.
     *
     * @return array{countries: array<int, array<string, mixed>>, default_country_id: int, supports_default: bool}|null
     */
    public function countries(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $data = $this->ps->callBridge('address.countries', []);
        } catch (PsUpstreamException) {
            return null;
        }

        $countries = array_values(array_filter((array) ($data['countries'] ?? []), fn ($c) => is_array($c) && (int) ($c['id'] ?? 0) > 0));
        if ($countries === []) {
            return null;
        }

        $result = [
            'countries' => array_map(fn (array $c) => [
                'id' => (int) $c['id'],
                'name' => (string) ($c['name'] ?? ''),
                'iso' => (string) ($c['iso'] ?? ''),
                'has_states' => (bool) ($c['has_states'] ?? false),
                'zip_required' => (bool) ($c['zip_required'] ?? false),
                'zip_format' => (string) ($c['zip_format'] ?? ''),
                'dni_required' => (bool) ($c['dni_required'] ?? false),
            ], $countries),
            'default_country_id' => (int) ($data['default_country_id'] ?? 6) ?: 6,
            'supports_default' => (bool) ($data['supports_default'] ?? false),
        ];

        Cache::put(self::CACHE_KEY, $result, (int) config('helpdeskprestashop.ext.address.countries_cache_ttl', 3600));

        return $result;
    }
}
