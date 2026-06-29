<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * SSRF protection shared by services that fetch attacker-influenced URLs (the
 * http_request node and the inbound voice-note download). Validates the host
 * against private/reserved IP ranges and pins the connection to the resolved IP
 * so a DNS-rebinding response can't steer the request to an internal address.
 */
trait GuardsAgainstSsrf
{
    /**
     * Returns curl options that pin the request to the validated IP, an empty
     * array when private-host blocking is disabled, or null when the URL must be
     * rejected (bad scheme/host or private/reserved target).
     *
     * @return array<int, array<int, string>>|null
     */
    protected function ssrfSafeCurlOptions(string $url): ?array
    {
        $parts = parse_url($url);

        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        if (! config('helpdeskchatflow.http.block_private_hosts', true)) {
            return [];
        }

        $host = $parts['host'];
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            Log::warning('Blocked private/reserved host in outbound request', ['host' => $host, 'ip' => $ip]);

            return null;
        }

        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        return [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]];
    }
}
