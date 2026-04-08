<?php

namespace Modules\Page\Services;

use Illuminate\Support\Facades\DB;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageView;

class PageAnalyticsService
{
    /**
     * Views per day for the last N days (for a line chart).
     *
     * @return array<int, array{date: string, views: int}>
     */
    public function viewsByDay(Page $page, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->selectRaw('viewed_date as date, COUNT(*) as views')
            ->groupBy('viewed_date')
            ->orderBy('viewed_date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'views' => (int) $row->views])
            ->toArray();
    }

    /**
     * Views breakdown by device type (for a donut chart).
     *
     * @return array<int, array{device: string, views: int}>
     */
    public function viewsByDevice(Page $page, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->selectRaw('device_type as device, COUNT(*) as views')
            ->groupBy('device_type')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => ['device' => $row->device, 'views' => (int) $row->views])
            ->toArray();
    }

    /**
     * Top referrer domains (for a table).
     *
     * @return array<int, array{domain: string, views: int}>
     */
    public function topReferrers(Page $page, int $days = 30, int $limit = 10): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->whereNotNull('referrer_domain')
            ->selectRaw('referrer_domain as domain, COUNT(*) as views')
            ->groupBy('referrer_domain')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['domain' => $row->domain, 'views' => (int) $row->views])
            ->toArray();
    }

    /**
     * Summary stats: total views, unique visitors, daily average, avg time on page, bounce rate.
     *
     * Uses a single aggregate query with conditional expressions to avoid
     * the separate avgTimeOnPage() and bounceRate() queries.
     *
     * @return array{total_views: int, unique_visitors: int, avg_daily: float, avg_time_on_page: float, bounce_rate: float}
     */
    public function summary(Page $page, int $days = 30, bool $previous = false): array
    {
        if ($previous) {
            $from = now()->subDays($days * 2 - 1)->toDateString();
            $to = now()->subDays($days)->toDateString();
        } else {
            $from = now()->subDays($days - 1)->toDateString();
            $to = now()->toDateString();
        }

        $stats = DB::table('page_views')
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->where('viewed_date', '<=', $to)
            ->selectRaw('
                COUNT(*) as total_views,
                COUNT(DISTINCT ip_hash) as unique_visitors,
                AVG(CASE WHEN time_on_page IS NOT NULL THEN time_on_page END) as avg_time_on_page,
                SUM(CASE WHEN bounced = 1 AND time_on_page IS NOT NULL THEN 1 ELSE 0 END) as bounced_count,
                COUNT(CASE WHEN time_on_page IS NOT NULL THEN 1 END) as time_sample_count
            ')
            ->first();

        $totalViews = (int) ($stats->total_views ?? 0);
        $timeSampleCount = (int) ($stats->time_sample_count ?? 0);

        $bounceRate = $timeSampleCount > 0
            ? round(($stats->bounced_count / $timeSampleCount) * 100, 1)
            : 0.0;

        return [
            'total_views' => $totalViews,
            'unique_visitors' => (int) ($stats->unique_visitors ?? 0),
            'avg_daily' => $days > 0 ? round($totalViews / $days, 1) : 0.0,
            'avg_time_on_page' => (float) round((float) ($stats->avg_time_on_page ?? 0.0), 1),
            'bounce_rate' => $bounceRate,
        ];
    }

    /**
     * Average time on page in seconds over the last N days.
     */
    public function avgTimeOnPage(Page $page, int $days = 30): float
    {
        $from = now()->subDays($days - 1)->toDateString();

        return (float) round(
            PageView::query()
                ->where('page_id', $page->id)
                ->where('viewed_date', '>=', $from)
                ->whereNotNull('time_on_page')
                ->avg('time_on_page') ?? 0.0,
            1
        );
    }

    /**
     * Bounce rate (percentage of sessions lasting < 10 seconds) over the last N days.
     */
    public function bounceRate(Page $page, int $days = 30): float
    {
        $from = now()->subDays($days - 1)->toDateString();

        $base = PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->whereNotNull('time_on_page');

        $total = (clone $base)->count();

        if ($total === 0) {
            return 0.0;
        }

        $bounced = (clone $base)->where('bounced', true)->count();

        return round(($bounced / $total) * 100, 1);
    }

    /**
     * Top pages by views over the last N days (for a global overview).
     *
     * @return array<int, array{page_id: int, title: string, views: int}>
     */
    public function topPages(int $days = 30, int $limit = 10): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('viewed_date', '>=', $from)
            ->selectRaw('page_id, COUNT(*) as views')
            ->groupBy('page_id')
            ->orderByDesc('views')
            ->limit($limit)
            ->with('page:id,title,slug')
            ->get()
            ->map(fn ($row) => [
                'page_id' => $row->page_id,
                'title' => $row->page?->title ?? '(eliminada)',
                'slug' => $row->page?->slug ?? '',
                'views' => (int) $row->views,
            ])
            ->toArray();
    }

    /**
     * Views breakdown by browser (for a donut chart + list).
     *
     * @return array<int, array{browser: string, views: int}>
     */
    public function viewsByBrowser(Page $page, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->selectRaw("COALESCE(browser, 'Other') as browser, COUNT(*) as views")
            ->groupBy('browser')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => ['browser' => $row->browser, 'views' => (int) $row->views])
            ->toArray();
    }

    /**
     * Views breakdown by country code (for a list).
     *
     * @return array<int, array{country: string, views: int, percentage: float}>
     */
    public function viewsByCountry(Page $page, int $days = 30, int $limit = 10): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        $data = PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->selectRaw("COALESCE(country_code, 'XX') as country, COUNT(*) as views")
            ->groupBy('country_code')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        $total = $data->sum('views') ?: 1;

        return $data->map(fn ($row) => [
            'country' => $row->country,
            'views' => (int) $row->views,
            'percentage' => round(($row->views / $total) * 100, 1),
        ])->toArray();
    }

    /**
     * Views breakdown by locale/language (for a list).
     *
     * @return array<int, array{locale: string, views: int}>
     */
    public function viewsByLocale(Page $page, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->selectRaw("COALESCE(locale, 'unknown') as locale, COUNT(*) as views")
            ->groupBy('locale')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => ['locale' => $row->locale, 'views' => (int) $row->views])
            ->toArray();
    }

    /**
     * Raw view records for a page in the given period, formatted for export.
     *
     * @return array<int, array<string, string>>
     */
    public function exportViewsData(Page $page, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return PageView::query()
            ->where('page_id', $page->id)
            ->where('viewed_date', '>=', $from)
            ->orderByDesc('viewed_at')
            ->get(['viewed_date', 'device_type', 'browser', 'referrer_domain', 'locale', 'viewed_at'])
            ->map(fn ($v) => [
                'Fecha' => $v->viewed_date->toDateString(),
                'Dispositivo' => $v->device_type ?? '',
                'Navegador' => $v->browser ?? '',
                'Referrer' => $v->referrer_domain ?? 'Directo',
                'Idioma' => $v->locale ?? '',
                'Hora' => $v->viewed_at->format('H:i:s'),
            ])
            ->toArray();
    }
}
