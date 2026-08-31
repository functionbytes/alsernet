<?php

namespace Modules\HelpdeskTickets\Support;

/**
 * SSRF guard para el host:puerto de los canales de correo (conexiones IMAP)
 * que alimentan FetchTicketEmailsJob. Mismo modelo de amenaza y misma lógica
 * que Modules\MailsSettings\Support\MailsSettingsUrlGuard::isHostAllowed()
 * (replicada aquí en vez de importada — HelpdeskTickets no declara
 * dependencia de MailsSettings en module.json, y es el mismo criterio ya
 * usado por ese guard frente a ErpEndpointUrlGuard): PERMITE rangos privados
 * RFC1918 (un servidor IMAP self-hosted puede vivir legítimamente en la red
 * interna Docker) y solo bloquea lo que nunca es un destino válido y sí es
 * peligroso:
 *
 *  - loopback (127.0.0.0/8, ::1) → self-SSRF a servicios del propio contenedor
 *  - link-local (169.254.0.0/16, fe80::/10) → incluye la IP de metadata cloud
 *    169.254.169.254
 *
 * Resuelve DNS en validación, así que no defiende del todo contra DNS
 * rebinding; para el modelo de amenaza (un usuario del panel apuntando la
 * conexión IMAP a metadata/localhost para escanear puertos internos) basta
 * con bloquear los literales y su resolución.
 */
class TicketEmailChannelUrlGuard
{
    public static function isHostAllowed(?string $host): bool
    {
        if (! is_string($host) || $host === '') {
            return false;
        }

        $ips = self::resolve($host);

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
        // NO_PRIV_RANGE porque RFC1918 (red interna Docker) sí está permitido.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
