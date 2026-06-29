<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;

/**
 * Fetches live search-engine rankings for the target_keyword of each SeoMeta,
 * via a provider-agnostic interface. Swap providers by setting `SEO_SERP_PROVIDER`
 * (or seo.serp.provider in settings) to one of: serpapi, valueserp, dataforseo.
 *
 * Providers return the current position (1-100) for the given keyword+URL+country.
 * We then persist that position into `seo_metas.gsc_position` so the analytics
 * dashboard shows real SERP data instead of only Search Console data.
 *
 * This service degrades gracefully: if no provider key is configured, calls
 * return null instead of erroring — the dashboard just shows no data.
 */
class KeywordRankingService
{
    /**
     * Look up the current ranking for a keyword + URL. Returns an integer 1-100
     * if found, null if not ranking in top-100 or no provider configured.
     */
    public function lookup(string $keyword, string $url, string $country = 'us', string $language = 'en'): ?int
    {
        $provider = (string) seo_setting('serp.provider', config('Seo.serp.provider', ''));
        $apiKey = (string) seo_setting('serp.api_key', config('Seo.serp.api_key', ''));

        if ($provider === '' || $apiKey === '') {
            return null;
        }

        try {
            return match ($provider) {
                'serpapi' => $this->lookupSerpApi($keyword, $url, $country, $language, $apiKey),
                'valueserp' => $this->lookupValueSerp($keyword, $url, $country, $language, $apiKey),
                'dataforseo' => $this->lookupDataForSeo($keyword, $url, $country, $language, $apiKey),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning("Keyword ranking failed [{$provider}]: ".$e->getMessage(), [
                'keyword' => $keyword,
                'url' => $url,
            ]);

            return null;
        }
    }

    /**
     * Batch-update rankings for all SeoMeta with a target_keyword.
     * Returns the number of records updated.
     */
    public function updateAll(int $limit = 50): int
    {
        $metas = SeoMeta::query()
            ->whereNotNull('target_keyword')
            ->where('target_keyword', '!=', '')
            ->whereNotNull('canonical_url')
            ->limit($limit)
            ->get(['id', 'target_keyword', 'canonical_url']);

        $updated = 0;
        foreach ($metas as $meta) {
            $position = $this->lookup($meta->target_keyword, $meta->canonical_url);

            if ($position === null) {
                continue;
            }

            $meta->forceFill([
                'gsc_position' => $position,
                'gsc_updated_at' => now(),
            ])->saveQuietly();

            $updated++;

            // Respect provider rate limits
            usleep(500_000);
        }

        return $updated;
    }

    private function lookupSerpApi(string $keyword, string $url, string $country, string $language, string $apiKey): ?int
    {
        $response = Http::timeout(20)->get('https://serpapi.com/search.json', [
            'q' => $keyword,
            'gl' => $country,
            'hl' => $language,
            'num' => 100,
            'api_key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($response->json('organic_results') ?? [] as $result) {
            if (isset($result['link']) && parse_url($result['link'], PHP_URL_HOST) === $host) {
                return (int) ($result['position'] ?? 0) ?: null;
            }
        }

        return null;
    }

    private function lookupValueSerp(string $keyword, string $url, string $country, string $language, string $apiKey): ?int
    {
        $response = Http::timeout(20)->get('https://api.valueserp.com/search', [
            'api_key' => $apiKey,
            'q' => $keyword,
            'gl' => $country,
            'hl' => $language,
            'num' => 100,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        foreach ($response->json('organic_results') ?? [] as $result) {
            if (isset($result['link']) && parse_url($result['link'], PHP_URL_HOST) === $host) {
                return (int) ($result['position'] ?? 0) ?: null;
            }
        }

        return null;
    }

    private function lookupDataForSeo(string $keyword, string $url, string $country, string $language, string $apiKey): ?int
    {
        // DataForSEO uses basic auth with login:password, pass as "login:password" in apiKey
        [$login, $password] = array_pad(explode(':', $apiKey, 2), 2, '');

        $response = Http::timeout(30)
            ->withBasicAuth($login, $password)
            ->post('https://api.dataforseo.com/v3/serp/google/organic/live/advanced', [[
                'keyword' => $keyword,
                'location_code' => 2840,  // US by default
                'language_code' => $language,
                'depth' => 100,
            ]]);

        if (! $response->successful()) {
            return null;
        }

        $items = $response->json('tasks.0.result.0.items') ?? [];
        $host = parse_url($url, PHP_URL_HOST);
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'organic' && parse_url($item['url'] ?? '', PHP_URL_HOST) === $host) {
                return (int) ($item['rank_absolute'] ?? 0) ?: null;
            }
        }

        return null;
    }
}
