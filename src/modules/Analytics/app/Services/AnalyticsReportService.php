<?php

namespace Modules\Analytics\Services;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\MetricAggregation;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Analytics\Exports\AnalyticsReportExport;
use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;

class AnalyticsReportService
{
    /**
     * Fetch report data from GA4 for the given period.
     *
     * @return array{type: string, generated_at: string, period: array, overview: array, top_pages: array, top_browsers: array, top_referrers: array}
     */
    public function generateReport(Period $period, string $reportType): array
    {
        $overviewResponse = Analytics::dateRange($period)
            ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
            ->metricAggregation(MetricAggregation::TOTAL)
            ->get();

        $totals = collect($overviewResponse->metricAggregationsTable)
            ->firstWhere('aggregation', 'TOTAL') ?? [];

        $topPages = Analytics::fetchMostVisitedPages($period, 10)
            ->map(fn ($page) => [
                'title' => $page['pageTitle'] ?? 'Unknown',
                'url' => $page['fullPageUrl'] ?? '#',
                'views' => (int) ($page['screenPageViews'] ?? 0),
            ])
            ->toArray();

        $topBrowsers = Analytics::fetchTopBrowsers($period)
            ->map(fn ($item) => [
                'name' => $item['browser'] ?? 'Unknown',
                'sessions' => (int) ($item['sessions'] ?? 0),
            ])
            ->toArray();

        $topReferrers = Analytics::fetchTopReferrers($period)
            ->map(fn ($item) => [
                'source' => $item['sessionSource'] ?? 'Direct',
                'views' => (int) ($item['screenPageViews'] ?? 0),
            ])
            ->toArray();

        return [
            'type' => $reportType,
            'generated_at' => now()->toIso8601String(),
            'period' => [
                'start' => $period->startDate->toDateString(),
                'end' => $period->endDate->toDateString(),
            ],
            'overview' => [
                'sessions' => (int) ($totals['sessions'] ?? 0),
                'users' => (int) ($totals['totalUsers'] ?? 0),
                'pageviews' => (int) ($totals['screenPageViews'] ?? 0),
                'bounce_rate' => (float) ($totals['bounceRate'] ?? 0),
            ],
            'top_pages' => $topPages,
            'top_browsers' => $topBrowsers,
            'top_referrers' => $topReferrers,
        ];
    }

    /**
     * Persist the report as JSON in storage/app/analytics-reports/ and return the absolute path.
     */
    public function saveReport(array $report, string $reportType): string
    {
        $timestamp = Carbon::parse($report['generated_at'])->format('Y-m-d_H-i-s');
        $filename = "analytics_report_{$reportType}_{$timestamp}.json";
        $path = storage_path("app/analytics-reports/{$filename}");

        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * Generate a temporary report file in the given format and return its absolute path.
     */
    public function generateReportFile(array $report, string $format): string
    {
        $timestamp = Carbon::parse($report['generated_at'])->format('Y-m-d_H-i-s');
        $dir = storage_path('app/analytics-reports/tmp');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return match ($format) {
            'excel' => $this->generateExcelFile($report, $timestamp),
            'csv' => $this->generateCsvFile($dir, $report, $timestamp),
            default => $this->generateJsonFile($dir, $report, $timestamp),
        };
    }

    /**
     * Build a human-readable summary array from report overview data.
     *
     * @return array<string, string>
     */
    public function buildSummary(array $report): array
    {
        $overview = $report['overview'];

        return [
            __('analytics::analytics.reports.sessions') => number_format($overview['sessions']),
            __('analytics::analytics.reports.users') => number_format($overview['users']),
            __('analytics::analytics.reports.pageviews') => number_format($overview['pageviews']),
            __('analytics::analytics.reports.bounce_rate') => round($overview['bounce_rate'] * 100, 1).'%',
            __('analytics::analytics.reports.period') => $report['period']['start'].' → '.$report['period']['end'],
        ];
    }

    /**
     * Calculate the next run time for a given frequency.
     * Uses precise boundaries: daily → start of next day, weekly → start of next week, monthly → start of next month.
     */
    public function calculateNextRun(string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => now()->addWeek()->startOfWeek(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default => now()->addDay()->startOfDay(),
        };
    }

    private function generateExcelFile(array $report, string $timestamp): string
    {
        Excel::store(
            new AnalyticsReportExport($report),
            "analytics-reports/tmp/analytics_{$timestamp}.xlsx",
            'local'
        );

        return storage_path("app/analytics-reports/tmp/analytics_{$timestamp}.xlsx");
    }

    private function generateCsvFile(string $dir, array $report, string $timestamp): string
    {
        $path = "{$dir}/analytics_{$timestamp}.csv";
        $overview = $report['overview'];

        $rows = [
            [__('analytics::analytics.reports.sessions'), $overview['sessions']],
            [__('analytics::analytics.reports.users'), $overview['users']],
            [__('analytics::analytics.reports.pageviews'), $overview['pageviews']],
            [__('analytics::analytics.reports.bounce_rate'), round($overview['bounce_rate'] * 100, 1).'%'],
            ['', ''],
            [__('analytics::analytics.dashboard.top_pages'), __('analytics::analytics.dashboard.pageviews')],
        ];

        foreach ($report['top_pages'] as $page) {
            $rows[] = [$page['title'], $page['views']];
        }

        $handle = fopen($path, 'w');

        try {
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        } finally {
            fclose($handle);
        }

        return $path;
    }

    private function generateJsonFile(string $dir, array $report, string $timestamp): string
    {
        $path = "{$dir}/analytics_{$timestamp}.json";

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
