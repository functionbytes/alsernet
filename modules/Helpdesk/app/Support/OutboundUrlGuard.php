<?php

namespace Modules\Helpdesk\Support;

/**
 * SSRF guard for user-supplied outbound URLs (automation/workflow webhooks,
 * http_request actions). Rejects anything that is not a plain http(s) URL whose
 * host resolves exclusively to public IP addresses — blocking loopback, private
 * (RFC1918), link-local (incl. the 169.254.169.254 cloud metadata endpoint) and
 * other reserved ranges.
 *
 * Note: this resolves DNS at validation time, so it does not fully defeat DNS
 * rebinding (the host could resolve to a private IP between check and request).
 * For the threat model here — operators pointing automations at internal
 * services — blocking literal internal URLs and metadata IPs is the goal.
 */
class OutboundUrlGuard
{
    public static function isSafe(?string $url): bool
    {
        return self::publicIps($url) !== [];
    }

    /**
     * Resolve the URL's host and return its IPs only when EVERY resolved
     * address (A + AAAA) is public; otherwise an empty array. Exposed so
     * callers that pin the connection to the validated IPs (DNS-rebinding
     * defence via CURLOPT_RESOLVE — e.g. HelpdeskChatFlow's GuardsAgainstSsrf)
     * can reuse this resolution instead of duplicating it.
     *
     * @return array<int, string> Validated public IPs, or [] when the URL is unsafe.
     */
    public static function publicIps(?string $url): array
    {
        if (! is_string($url) || $url === '') {
            return [];
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return [];
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return [];
        }

        $ips = self::resolve($parts['host']);

        if ($ips === []) {
            return [];
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return [];
            }
        }

        return $ips;
    }

    /**
     * @return array<int, string>
     */
    private static function resolve(string $host): array
    {
        // Strip an IPv6 literal's brackets: http://[::1]/ → ::1
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

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
