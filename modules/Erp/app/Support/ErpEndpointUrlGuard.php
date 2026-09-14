<?php

namespace Modules\Erp\Support;

/**
 * SSRF guard para las URLs de los ERP Endpoints. A diferencia del
 * OutboundUrlGuard genérico (que exige IP pública), este PERMITE los rangos
 * privados RFC1918 porque los endpoints ERP legítimos viven en la red interna
 * Docker; solo bloquea lo que nunca es un ERP válido y sí es peligroso:
 *
 *  - loopback (127.0.0.0/8, ::1) → self-SSRF a servicios del propio contenedor
 *  - link-local (169.254.0.0/16, fe80::/10) → incluye la IP de metadata cloud
 *    169.254.169.254
 *  - esquemas no http(s)
 *
 * Resuelve DNS en validación, así que no defiende del todo contra DNS
 * rebinding; para el modelo de amenaza (un admin apuntando un endpoint a
 * metadata/localhost) basta con bloquear los literales y su resolución.
 */
class ErpEndpointUrlGuard
{
    public static function isAllowed(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $ips = self::resolve($parts['host']);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private static function resolve(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($aaaa as $record) {
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    private static function isBlockedIp(string $ip): bool
    {
        // Loopback (127.0.0.0/8, ::1) y link-local (169.254.0.0/16, fe80::/10):
        // FILTER_FLAG_NO_RES_RANGE marca ambos como reservados. No usamos
        // NO_PRIV_RANGE porque RFC1918 (red ERP interna) sí está permitido.
        $isReservedOrLoopback = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;

        return $isReservedOrLoopback;
    }
}
