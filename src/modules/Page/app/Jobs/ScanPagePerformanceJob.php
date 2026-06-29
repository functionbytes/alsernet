<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePerformanceMetric;

class ScanPagePerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public int $maxExceptions = 2;

    public function __construct(
        private readonly Page $page,
        private readonly string $strategy = 'mobile',
    ) {
        $this->queue = 'pagespeed';
    }

    /** @return int[] */
    public function backoff(): array
    {
        return [30, 120, 480];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('PageSpeed scan failed permanently', [
            'page_id' => $this->page->id,
            'strategy' => $this->strategy,
            'error' => $e->getMessage(),
        ]);
    }

    public function handle(): void
    {
        $apiKey = config('services.google.pagespeed_key');

        if (empty($apiKey)) {
            $this->fail(new \RuntimeException(
                'Google PageSpeed API key not configured. Set GOOGLE_PAGESPEED_KEY in your .env file.'
            ));

            return;
        }

        $url = $this->page->url;

        $response = Http::timeout(90)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
            'url' => $url,
            'strategy' => $this->strategy,
            'key' => $apiKey,
            'category' => ['performance', 'accessibility', 'seo', 'best-practices'],
        ]);

        if ($response->failed()) {
            Log::error('PageSpeed API error', [
                'page_id' => $this->page->id,
                'strategy' => $this->strategy,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $response->throw();
        }

        $data = $response->json();
        $lighthouse = $data['lighthouseResult'] ?? [];
        $categories = $lighthouse['categories'] ?? [];
        $audits = $lighthouse['audits'] ?? [];

        $opportunities = $this->extractOpportunities($audits);

        PagePerformanceMetric::create([
            'page_id' => $this->page->id,
            'strategy' => $this->strategy,
            'page_url' => $url,
            'performance_score' => $this->score($categories['performance']['score'] ?? null),
            'accessibility_score' => $this->score($categories['accessibility']['score'] ?? null),
            'seo_score' => $this->score($categories['seo']['score'] ?? null),
            'best_practices_score' => $this->score($categories['best-practices']['score'] ?? null),
            'lcp' => $this->intVal($audits['largest-contentful-paint']['numericValue'] ?? null),
            'fcp' => $this->intVal($audits['first-contentful-paint']['numericValue'] ?? null),
            'tbt' => $this->intVal($audits['total-blocking-time']['numericValue'] ?? null),
            'speed_index' => $this->intVal($audits['speed-index']['numericValue'] ?? null),
            'cls_score' => $this->intVal(($audits['cumulative-layout-shift']['numericValue'] ?? null) !== null
                ? ($audits['cumulative-layout-shift']['numericValue'] * 1000)
                : null),
            'ttfb' => $this->intVal($audits['server-response-time']['numericValue'] ?? null),
            'opportunities' => $opportunities,
        ]);
    }

    private function score(mixed $value): ?float
    {
        return $value !== null ? round((float) $value * 100, 1) : null;
    }

    private function intVal(mixed $value): ?int
    {
        return $value !== null ? (int) round((float) $value) : null;
    }

    /**
     * Extract top 5 opportunity audits (type=opportunity, score < 1).
     *
     * @param  array<string, mixed>  $audits
     * @return array<int, array<string, mixed>>
     */
    private function extractOpportunities(array $audits): array
    {
        $opportunities = [];

        foreach ($audits as $key => $audit) {
            $details = $audit['details'] ?? [];
            $score = $audit['score'] ?? 1;

            if (($details['type'] ?? '') !== 'opportunity' || $score >= 1) {
                continue;
            }

            $opportunities[] = [
                'id' => $key,
                'title' => $audit['title'] ?? $key,
                'description' => $audit['description'] ?? '',
                'score' => $score,
                'savings_ms' => $this->intVal($details['overallSavingsMs'] ?? null),
            ];
        }

        usort($opportunities, fn ($a, $b) => $a['score'] <=> $b['score']);

        return array_slice($opportunities, 0, 5);
    }
}
