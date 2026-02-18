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

        $prefetchTags = '';

        foreach ($hosts as $host) {
            $prefetchTags .= '<link rel="dns-prefetch" href="//'.$host.'">';
        }

        return str_replace('</head>', $prefetchTags.'</head>', $buffer);
    }

    /**
     * Extract unique hostnames from URLs found in the HTML buffer.
     *
     * @return string[]
     */
    private function extractHosts(string $buffer): array
    {
        $currentHost = request()->getHost();
        $hosts = [];

        preg_match_all('/https?:\/\/([^\/\s"\']+)/i', $buffer, $matches);

        foreach ($matches[1] as $host) {
            if ($host !== $currentHost && ! in_array($host, $hosts, true)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }
}
