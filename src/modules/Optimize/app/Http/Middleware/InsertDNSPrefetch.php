<?php

namespace Modules\Optimize\Http\Middleware;

class InsertDNSPrefetch extends PageSpeed
{
    /**
     * Origins that are critical enough to warrant a preconnect hint.
     * Browsers should open at most ~6 connections, but Lighthouse warns
     * when more than 4 preconnects are present. We keep the list tight.
     */
    private const CRITICAL_PRECONNECT_HOSTS = [
        'fonts.gstatic.com',
        'fonts.googleapis.com',
    ];

    public function apply(string $buffer): string
    {
        $hosts = $this->extractHosts($buffer);

        if (empty($hosts)) {
            return $buffer;
        }

        $tags = '';
        $preconnectCount = 0;
        $maxPreconnect = 4;

        foreach ($hosts as $host) {
            $isCritical = $this->isCriticalHost($host);

            if ($isCritical && $preconnectCount < $maxPreconnect && ! $this->hasExistingPreconnect($buffer, $host)) {
                $scheme = str_starts_with($host, 'https://') ? '' : 'https://';
                $tags .= '<link rel="preconnect" href="'.$scheme.$host.'" crossorigin>';
                $preconnectCount++;
            }

            // Non-critical hosts and critical hosts that already have preconnect
            // get a cheaper dns-prefetch instead (or nothing if already hinted)
            if (! $this->hasExistingDNSPrefetch($buffer, $host)) {
                $tags .= '<link rel="dns-prefetch" href="//'.$host.'">';
            }
        }

        if ($tags === '') {
            return $buffer;
        }

        return str_replace('</head>', $tags.'</head>', $buffer);
    }

    /**
     * Extract unique external hostnames from URLs in the HTML buffer,
     * excluding hosts that already have a dns-prefetch or preconnect hint.
     *
     * @return string[]
     */
    private function extractHosts(string $buffer): array
    {
        $currentHost = request()->getHost();
        $seen = [];

        preg_match_all('/https?:\/\/([^\/\s"\'<>]+)/i', $buffer, $matches);

        foreach ($matches[1] as $rawHost) {
            $host = strtolower(explode(':', $rawHost)[0]);

            if ($host !== $currentHost && ! isset($seen[$host])) {
                $seen[$host] = true;
            }
        }

        return array_keys($seen);
    }

    private function isCriticalHost(string $host): bool
    {
        foreach (self::CRITICAL_PRECONNECT_HOSTS as $critical) {
            if (str_contains($host, $critical)) {
                return true;
            }
        }

        // Google Maps is critical only when an iframe or API call is present
        if (str_contains($host, 'maps.googleapis.com') || str_contains($host, 'maps.gstatic.com')) {
            return true;
        }

        return false;
    }

    private function hasExistingPreconnect(string $buffer, string $host): bool
    {
        $patterns = [
            'preconnect"\s+href="https?://'.preg_quote($host, '#').'"',
            'preconnect"\s+href="//'.preg_quote($host, '#').'"',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match('#'.$pattern.'#i', $buffer)) {
                return true;
            }
        }

        return false;
    }

    private function hasExistingDNSPrefetch(string $buffer, string $host): bool
    {
        $patterns = [
            'dns-prefetch"\s+href="https?://'.preg_quote($host, '#').'"',
            'dns-prefetch"\s+href="//'.preg_quote($host, '#').'"',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match('#'.$pattern.'#i', $buffer)) {
                return true;
            }
        }

        return false;
    }
}
