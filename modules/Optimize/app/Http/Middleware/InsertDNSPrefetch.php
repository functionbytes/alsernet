<?php

namespace Modules\Optimize\Http\Middleware;

class InsertDNSPrefetch extends PageSpeed
{
    public function apply(string $buffer): string
    {
        $hosts = $this->extractHosts($buffer);

        if (empty($hosts)) {
            return $buffer;
        }

        $tags = '';

        foreach ($hosts as $host) {
            // dns-prefetch for broad support; preconnect for modern browsers
            $tags .= '<link rel="preconnect" href="//'.$host.'" crossorigin>';
            $tags .= '<link rel="dns-prefetch" href="//'.$host.'">';
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

        // Seed already-hinted hosts so we don't inject duplicates
        preg_match_all('/<link[^>]+rel=["\'](?:dns-prefetch|preconnect)["\'][^>]*>/i', $buffer, $existingTags);
        foreach ($existingTags[0] as $tag) {
            if (preg_match('/href=["\']\/\/([^"\'\/\s]+)["\']/', $tag, $m)) {
                $seen[strtolower($m[1])] = 'existing';
            }
        }

        preg_match_all('/https?:\/\/([^\/\s"\'<>]+)/i', $buffer, $matches);

        foreach ($matches[1] as $rawHost) {
            $host = strtolower(explode(':', $rawHost)[0]);

            if ($host !== $currentHost && ! isset($seen[$host])) {
                $seen[$host] = 'new';
            }
        }

        return array_keys(array_filter($seen, fn (string $v) => $v === 'new'));
    }
}
