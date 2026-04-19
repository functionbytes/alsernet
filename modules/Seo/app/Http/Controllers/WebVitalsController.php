<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Modules\Seo\Models\SeoWebVital;

/**
 * Admin dashboard for Core Web Vitals: p75 aggregates per URL over the last
 * 28 days, following Google's methodology. The browser beacon is handled by
 * `SeoWebVitalsController`.
 */
class WebVitalsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $since = now()->subDays(28);

        // Global p75 per metric (last 28 days)
        $globalP75 = $this->computeP75(null, $since);

        // Worst pages per metric (rating = poor, sorted by sample count)
        $worstPages = DB::table('seo_web_vitals')
            ->selectRaw('url_path, metric, COUNT(*) as samples, AVG(value) as avg_value')
            ->where('captured_at', '>=', $since)
            ->where('rating', 'poor')
            ->groupBy('url_path', 'metric')
            ->having('samples', '>=', 3)
            ->orderByDesc('samples')
            ->limit(20)
            ->get();

        // Device breakdown
        $byDevice = DB::table('seo_web_vitals')
            ->selectRaw('device, metric, AVG(value) as avg_value, COUNT(*) as samples')
            ->where('captured_at', '>=', $since)
            ->groupBy('device', 'metric')
            ->get();

        $thresholds = SeoWebVital::THRESHOLDS;
        $totalSamples = SeoWebVital::since($since)->count();

        return view('Seo::settings.web-vitals.index', compact(
            'globalP75', 'worstPages', 'byDevice', 'thresholds', 'totalSamples', 'since'
        ));
    }

    public function show(string $path): View
    {
        $since = now()->subDays(28);
        $normalizedPath = '/'.ltrim($path, '/');

        $p75 = $this->computeP75($normalizedPath, $since);

        $trend = DB::table('seo_web_vitals')
            ->selectRaw('DATE(captured_at) as date, metric, COUNT(*) as samples, AVG(value) as avg_value')
            ->where('url_path', $normalizedPath)
            ->where('captured_at', '>=', $since)
            ->groupBy('date', 'metric')
            ->orderBy('date')
            ->get();

        $thresholds = SeoWebVital::THRESHOLDS;

        return view('Seo::settings.web-vitals.show', compact(
            'normalizedPath', 'p75', 'trend', 'thresholds', 'since'
        ));
    }

    /**
     * Compute the p75 value for each metric, optionally filtered by path.
     *
     * @return array<string, array{value: float, rating: string, samples: int}>
     */
    private function computeP75(?string $path, \DateTimeInterface $since): array
    {
        $days = max(1, (int) now()->diffInDays($since, true));
        $results = [];

        foreach (array_keys(SeoWebVital::THRESHOLDS) as $metric) {
            $countQuery = SeoWebVital::query()
                ->forMetric($metric)
                ->since($since);

            if ($path) {
                $countQuery->forUrlPath($path);
            }

            $count = (int) $countQuery->count();

            if ($count === 0) {
                $results[$metric] = ['value' => 0.0, 'rating' => 'unknown', 'samples' => 0];

                continue;
            }

            $p75 = SeoWebVital::p75($metric, $path, $days) ?? 0.0;

            $results[$metric] = [
                'value' => round($p75, 3),
                'rating' => SeoWebVital::rate($metric, $p75),
                'samples' => $count,
            ];
        }

        return $results;
    }

    private function detectDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent) === 1 ? 'mobile' : 'desktop';
    }
}
