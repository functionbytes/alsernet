<?php

namespace Modules\Supplier\Traits;

/**
 * Provides SSRF guards for outbound HTTP/FTP requests.
 *
 * Resolves the target host via DNS and rejects loopback, link-local,
 * RFC-1918 private ranges, IPv6 unique/link-local addresses, multicast,
 * reserved ranges and well-known cloud metadata hostnames before any
 * connection is opened.
 */
trait ValidatesPublicUrl
{
    /**
     * Hostnames that must never be reached, regardless of DNS resolution.
     *
     * @var list<string>
     */
    protected array $blockedHostnames = [
        'localhost',
        'host.docker.internal',
        'metadata.google.internal',
        'metadata',
        'metadata.goog',
    ];

    /**
     * Assert that a URL points to a public, externally routable address.
     *
     * @throws \InvalidArgumentException when the URL targets a non-public address.
     */
    protected function assertUrlIsPublic(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Only http(s) URLs are allowed.');
        }

        $this->assertHostIsPublic($host);
    }

    /**
     * Assert that a bare host (without scheme) resolves to a public address.
     *
     * Useful for protocols such as FTP/SFTP where only the host is stored.
     *
     * @throws \InvalidArgumentException when the host targets a non-public address.
     */
    protected function assertHostIsPublic(string $host): void
    {
        $host = strtolower(trim($host));

        // parse_url() keeps the brackets around IPv6 literals (e.g. "[::1]").
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if ($host === '' || $this->isBlockedHostname($host)) {
            throw new \InvalidArgumentException('Internal hostnames are not allowed.');
        }

        $ips = $this->resolveHostIps($host);

        if ($ips === []) {
            throw new \InvalidArgumentException('Could not resolve hostname.');
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw new \InvalidArgumentException('Private or reserved IP ranges are not allowed.');
            }
        }
    }

    /**
     * Non-throwing variant of {@see assertUrlIsPublic()}.
     */
    protected function urlIsPublic(string $url): bool
    {
        try {
            $this->assertUrlIsPublic($url);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Resolve every A/AAAA record for a host (plus the host itself if it is a literal IP).
     *
     * @return list<string>
     */
    protected function resolveHostIps(string $host): array
    {
        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (! empty($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    protected function isBlockedHostname(string $host): bool
    {
        return in_array(strtolower($host), $this->blockedHostnames, true);
    }

    /**
     * Detect private, reserved or otherwise non-routable IPv4/IPv6 addresses.
     */
    protected function isPrivateIp(string $ip): bool
    {
        $publicV4OrV6 = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($publicV4OrV6 === false) {
            return true;
        }

        // Belt-and-braces IPv6 checks: ::1 (loopback), fc00::/7 (unique local),
        // fe80::/10 (link-local) and IPv4-mapped IPv6 addresses.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = @inet_pton($ip);

            if ($packed === false) {
                return true;
            }

            if ($packed === inet_pton('::1')) {
                return true;
            }

            $firstByte = ord($packed[0]);
            $secondByte = ord($packed[1]);

            if (($firstByte & 0xFE) === 0xFC) {
                return true; // fc00::/7
            }

            if ($firstByte === 0xFE && ($secondByte & 0xC0) === 0x80) {
                return true; // fe80::/10
            }

            // IPv4-mapped (::ffff:0:0/96): re-check the embedded IPv4.
            if (str_starts_with($ip, '::ffff:') && str_contains($ip, '.')) {
                $embedded = substr($ip, strrpos($ip, ':') + 1);

                return $this->isPrivateIp($embedded);
            }
        }

        return false;
    }
}
