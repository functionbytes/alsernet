<?php

namespace Modules\Supplier\Support;

/**
 * 29-sep-2026: lista blanca de hosts para los endpoints del ERP (modelo,
 * característica e interno). Son hosts internos, así que no sirve la
 * comprobación de "URL pública": solo se aceptan los hosts conocidos del ERP
 * (config supplier.erp_allowed_hosts) más el de ERP_INTERNAL_URL. Se valida al
 * guardar, al probar y antes de enviar contenido al ERP.
 */
class ErpEndpointGuard
{
    /**
     * @return array<int, string>
     */
    public static function allowedHosts(): array
    {
        $hosts = (array) config('supplier.erp_allowed_hosts', []);

        $internalHost = parse_url((string) config('supplier.erp_internal_url', ''), PHP_URL_HOST);
        if ($internalHost) {
            $hosts[] = $internalHost;
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($host) => strtolower(trim((string) $host)),
            $hosts
        ))));
    }

    public static function isAllowed(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return in_array(strtolower($parts['host']), self::allowedHosts(), true);
    }
}
