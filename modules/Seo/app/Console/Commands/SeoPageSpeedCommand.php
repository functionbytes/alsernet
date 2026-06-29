<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoPagespeedSnapshot;

/**
 * Polls Google PageSpeed Insights API for the top URLs and stores a weekly
 * snapshot. Requires an API key (free, quota 25k requests/day) configured
 * via `SEO_PAGESPEED_API_KEY` or the admin Settings UI.
 *
 * Usage:
 *   php artisan seo:pagespeed                 # Top 20 URLs, mobile
 *   php artisan seo:pagespeed --url=https://…  # Single URL
 *   php artisan seo:pagespeed --desktop        # Desktop strategy
 */
class SeoPageSpeedCommand extends Command
{
    protected $signature = 'seo:pagespeed
                            {--url= : Audit a single URL instead of top-20}
                            {--desktop : Use desktop strategy (default is mobile)}
                            {--limit=20 : How many top URLs to audit}';

    protected $description = 'Fetch Core Web Vitals and PageSpeed scores from the Google API and store snapshots';

    private const API = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function handle(): int
    {
        $apiKey = (string) seo_setting('pagespeed_api_key', config('Seo.pagespeed_api_key', ''));

        if ($apiKey === '') {
            $this->error('Falta SEO_PAGESPEED_API_KEY. Configúralo en .env o en /panel/setting/seo/settings.');

            return self::FAILURE;
        }

        $strategy = $this->option('desktop') ? 'desktop' : 'mobile';
        $urls = $this->resolveUrls();

        if (empty($urls)) {
            $this->warn('No se encontraron URLs para auditar.');

            return self::SUCCESS;
        }

        $this->info('Auditando '.count($urls)." URL(s) con estrategia {$strategy}...");

        $saved = 0;
        foreach ($urls as $url) {
            try {
                $snapshot = $this->audit($url, $strategy, $apiKey);
                if ($snapshot) {
                    $saved++;
                    $this->line(sprintf(
                        '  ✓ %-60s perf=%s lcp=%sms cls=%s',
                        Str::limit($url, 60),
                        $snapshot->performance ?? '—',
                        $snapshot->lcp_ms ? round((float) $snapshot->lcp_ms) : '—',
                        $snapshot->cls !== null ? round((float) $snapshot->cls, 2) : '—'
                    ));
                }
            } catch (\Throwable $e) {
                $this->line('  ✗ '.Str::limit($url, 60).' — '.$e->getMessage());
            }

            // Rate limit: 25k/day = ~17 req/min. 4s between requests is safe.
            if (count($urls) > 1) {
                usleep(4_000_000);
            }
        }

        $this->info("Snapshots guardados: {$saved}/".count($urls));

        return self::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function resolveUrls(): array
    {
        if ($single = $this->option('url')) {
            return [$single];
        }

        return SeoMeta::query()
            ->whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->orderByDesc('gsc_clicks')
            ->limit((int) $this->option('limit'))
            ->pluck('canonical_url')
            ->unique()
            ->values()
            ->all();
    }

    private function audit(string $url, string $strategy, string $apiKey): ?SeoPagespeedSnapshot
    {
        $response = Http::timeout(60)->get(self::API, [
            'url' => $url,
            'strategy' => $strategy,
            'key' => $apiKey,
            'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $result = $response->json('lighthouseResult');
        $audits = $result['audits'] ?? [];
        $categories = $result['categories'] ?? [];

        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return SeoPagespeedSnapshot::create([
            'url' => $url,
            'url_path' => $path,
            'strategy' => $strategy,
            'performance' => $this->scorePercent($categories['performance'] ?? null),
            'accessibility' => $this->scorePercent($categories['accessibility'] ?? null),
            'best_practices' => $this->scorePercent($categories['best-practices'] ?? null),
            'seo' => $this->scorePercent($categories['seo'] ?? null),
            'lcp_ms' => $audits['largest-contentful-paint']['numericValue'] ?? null,
            'inp_ms' => $audits['interaction-to-next-paint']['numericValue'] ?? null,
            'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
            'fcp_ms' => $audits['first-contentful-paint']['numericValue'] ?? null,
            'ttfb_ms' => $audits['server-response-time']['numericValue'] ?? null,
            'captured_at' => now(),
        ]);
    }

    private function scorePercent(?array $category): ?int
    {
        if (! $category || ! isset($category['score'])) {
            return null;
        }

        return (int) round(((float) $category['score']) * 100);
    }
}
