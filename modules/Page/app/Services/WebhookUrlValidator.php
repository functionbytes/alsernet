<?php

namespace Modules\Page\Services;

class WebhookUrlValidator
{
    private const BLOCKED_HOSTS = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];

    private const ALLOWED_PORTS = [80, 443, 8080, 8443];

    /**
     * Validate that a webhook URL is safe to send requests to.
     *
     * Blocks private/reserved IP ranges, localhost, and non-standard ports
     * to prevent SSRF attacks targeting internal services.
     *
     * @throws \InvalidArgumentException
     */
    public static function validate(string $url): void
    {
        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Webhook URL must use http or https scheme.');
        }

        $host = $parsed['host'] ?? '';
        $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);

        if (! in_array((int) $port, self::ALLOWED_PORTS, true)) {
            throw new \InvalidArgumentException("Webhook URL port {$port} is not allowed.");
        }

        if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
            throw new \InvalidArgumentException('Webhook URL points to a blocked host.');
        }

        // Resolve hostname to IP and verify it is not in a private/reserved range.
        // gethostbyname() returns the original host unchanged when resolution fails,
        // which would silently bypass the check. Treat that as an unresolvable host.
        $resolved = gethostbyname($host);

        if ($resolved === $host) {
            throw new \InvalidArgumentException('La URL del webhook no pudo resolverse.');
        }

        if (! filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \InvalidArgumentException('Webhook URL resolves to a private/reserved IP address.');
        }
    }

    public static function isValid(string $url): bool
    {
        try {
            static::validate($url);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
