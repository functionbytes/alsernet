<?php

namespace Modules\HelpdeskTranslate\Support;

/**
 * SSRF guard para el endpoint de LibreTranslate.
 *
 * A diferencia del OutboundUrlGuard genérico (que exige IP pública y usan los
 * webhooks salientes a terceros), este PERMITE los rangos privados RFC1918:
 * LibreTranslate es un servicio self-hosted que vive por diseño en la red
 * interna Docker — de hecho el endpoint configurado aquí es
 * `host.docker.internal`, que resuelve a 192.168.65.254.
 *
 * Con el guard genérico la validación rechazaba ese endpoint legítimo y, como
 * la regla se evalúa en cada guardado aunque el proveedor activo sea DeepL,
 * dejaba la pantalla entera de ajustes sin poder guardarse.
 *
 * Solo se bloquea lo que nunca es un LibreTranslate válido y sí es peligroso:
 *
 *  - loopback (127.0.0.0/8, ::1) → self-SSRF a servicios del propio contenedor
 *  - link-local (169.254.0.0/16, fe80::/10) → incluye la IP de metadata cloud
 *    169.254.169.254
 *  - esquemas que no sean http(s)
 *
 * Mismo criterio y misma redacción que ErpEndpointUrlGuard y
 * TicketEmailChannelUrlGuard, que resuelven este mismo caso (servicio interno
 * legítimo) en sus módulos. Se replica en vez de importarse para no crear una
 * dependencia de HelpdeskTranslate hacia Erp/HelpdeskTickets, igual que hacen
 * esos dos entre sí.
 *
 * Resuelve DNS en validación, así que no defiende del todo contra DNS
 * rebinding; para el modelo de amenaza (un admin apuntando el endpoint a
 * metadata/localhost) basta con bloquear los literales y su resolución.
 */
class LibreTranslateEndpointUrlGuard
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
        // NO_PRIV_RANGE porque RFC1918 (LibreTranslate en la red Docker) sí
        // está permitido.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
