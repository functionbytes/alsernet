<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class InternalLinkAnalyzer
{
    /**
     * Find pages that have no internal links pointing to them.
     * Scans a sample of sitemapped URLs for <a href> links and builds an inbound link map.
     *
     * @param  Collection<int, string>  $urls
     * @return array{orphans: array<string>, scanned: int, total_urls: int, inbound_counts: array<string, int>}
     */
    public function findOrphansByLinks(Collection $urls, int $limit = 20): array
    {
        $inboundLinks = [];
        $scanned = 0;

        foreach ($urls->take(30) as $url) {
            try {
                $response = Http::timeout(8)->get($url);
                if ($response->ok()) {
                    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $response->body(), $matches);
                    foreach ($matches[1] as $href) {
                        $normalized = $this->normalizeUrl($href, $url);
                        if ($normalized !== null) {
                            $inboundLinks[$normalized] = ($inboundLinks[$normalized] ?? 0) + 1;
                        }
                    }
                }
            } catch (\Throwable) {
                // Skip unreachable pages silently
            }

            $scanned++;
        }

        $orphans = $urls->filter(fn ($url) => ! isset($inboundLinks[$url]))->values();

        return [
            'orphans' => $orphans->take($limit)->toArray(),
            'scanned' => $scanned,
            'total_urls' => $urls->count(),
            'inbound_counts' => collect($inboundLinks)->sortDesc()->take(10)->toArray(),
        ];
    }

    private function normalizeUrl(string $href, string $baseUrl): ?string
    {
        if (str_starts_with($href, '#')
            || str_starts_with($href, 'mailto:')
            || str_starts_with($href, 'tel:')) {
            return null;
        }

        if (str_starts_with($href, '/')) {
            $parsed = parse_url($baseUrl);

            return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').$href;
        }

        if (str_starts_with($href, 'http')) {
            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            $hrefHost = parse_url($href, PHP_URL_HOST);

            return $baseHost === $hrefHost ? $href : null;
        }

        return null;
    }
}
