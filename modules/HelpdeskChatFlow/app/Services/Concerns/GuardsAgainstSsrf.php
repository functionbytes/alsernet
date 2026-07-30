<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;

/**
 * SSRF protection shared by services that fetch attacker-influenced URLs (the
 * http_request node and the inbound voice-note download).
 *
 * Host/IP validation is delegated to the core guard
 * (Modules\Helpdesk\Support\OutboundUrlGuard::publicIps), which resolves ALL
 * A and AAAA records and rejects the URL if any of them is private/reserved —
 * stronger than the previous single-gethostbyname lookup here. On top of that,
 * this trait keeps its own hardening: the connection is pinned to the
 * validated IPs via CURLOPT_RESOLVE so a DNS-rebinding response between the
 * check and the request can't steer it to an internal address.
 */
trait GuardsAgainstSsrf
{
    /**
     * Returns curl options that pin the request to the validated IPs, an empty
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

        $ips = OutboundUrlGuard::publicIps($url);

        if ($ips === []) {
            Log::warning('Blocked private/reserved host in outbound request', ['host' => $parts['host']]);

            return null;
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        // libcurl accepts multiple pinned addresses (comma separated); IPv6
        // literals go in brackets so the colons don't break the entry format.
        $pinned = implode(',', array_map(
            fn (string $ip): string => str_contains($ip, ':') ? '['.$ip.']' : $ip,
            $ips
        ));

        return [CURLOPT_RESOLVE => ["{$host}:{$port}:{$pinned}"]];
    }
}
