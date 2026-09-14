<?php

namespace Modules\Helpdesk\Support;

use Modules\Erp\Support\ErpEndpointUrlGuard;

/**
 * SSRF guard para la descarga server-side de adjuntos (media) recibidos por
 * webhooks de Meta (Facebook Messenger / Instagram).
 *
 * La URL de descarga puede proceder del payload del webhook (p. ej. adjuntos
 * tipo `fallback`/enlace que el usuario escribe por DM y que Meta simplemente
 * relaya), por lo que NO es de confianza aunque la firma HMAC del webhook sea
 * válida: la firma prueba que el evento vino de Meta, no que la URL embebida
 * apunte a un recurso seguro.
 *
 * A diferencia de {@see ErpEndpointUrlGuard} (que permite
 * RFC1918 porque el ERP vive en la red interna Docker), aquí se BLOQUEAN
 * también los rangos privados: un adjunto legítimo de Meta siempre resuelve a
 * una IP pública de su CDN (*.fbcdn.net, *.cdninstagram.com, lookaside.fbsbx.com).
 *
 * Nota: resuelve DNS en la validación, así que no defiende de DNS rebinding;
 * para el modelo de amenaza (payload apuntando a metadata cloud, loopback o red
 * interna) basta con bloquear los literales y su resolución.
 */
class OutboundMediaUrlGuard
{
    /**
     * Determina si es seguro hacer una petición HTTP server-side a la URL dada.
     */
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
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resuelve el host a todas sus IPs (IPv4 e IPv6). Un literal IP se devuelve
     * tal cual; un host no resoluble devuelve [] (y por tanto se bloquea).
     *
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

    /**
     * Una IP es pública si no cae en rangos privados (RFC1918, ULA fc00::/7) ni
     * reservados (loopback 127/8 y ::1, link-local 169.254/16 —incluye la IP de
     * metadata cloud 169.254.169.254— y fe80::/10, "this host" 0.0.0.0/8, etc.).
     */
    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
