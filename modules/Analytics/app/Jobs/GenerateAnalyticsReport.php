<?php

namespace Modules\Analytics\Jobs;

use Modules\Analytics\Facades\Analytics;
use Modules\Analytics\Period;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateAnalyticsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $reportType;
    protected ?string $email;

    /**
     * Create a new job instance.
     *
     * @param string $reportType 'daily', 'weekly', 'monthly'
     * @param string|null $email Email to send report to
     */
    public function __construct(string $reportType = 'daily', ?string $email = null)
    {
        $this->reportType = $reportType;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validar configuración
            if (!setting('google_analytics_property_id') || !setting('google_analytics_credentials')) {
                Log::warning('Analytics not configured. Skipping report generation.');
                return;
            }

            // Obtener período según tipo de reporte
            $period = $this->getPeriodByType();

            // Generar datos del reporte
            $report = $this->generateReport($period);

            // Guardar reporte
            $this->saveReport($report);

            // Enviar email si se proporciona
            if ($this->email) {
                $this->sendEmail($report);
            }

            Log::info("Analytics report generated: {$this->reportType}", $report);
        } catch (\Exception $e) {
            Log::error('Analytics report generation failed: ' . $e->getMessage());
            $this->failed($e);
        }
    }

    /**
     * Get period based on report type
     */
    protected function getPeriodByType(): Period
    {
        return match ($this->reportType) {
            'daily' => Period::days(1),
            'weekly' => Period::days(7),
            'monthly' => Period::days(30),
            default => Period::days(1),
        };
    }

    /**
     * Generate report data
     */
    protected function generateReport(Period $period): array
    {
        $analytics = Analytics::dateRange($period);

        // Overview stats
        $overview = $analytics
            ->metrics(['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'])
            ->get()
            ->getTotals();

        // Top pages
        $topPages = Analytics::fetchMostVisitedPages($period, 10)
            ->map(fn ($page) => [
                'title' => $page[0] ?? 'Unknown',
                'url' => $page[1] ?? '#',
                'views' => (int) ($page[2] ?? 0),
            ])
            ->toArray();

        // Top browsers
        $topBrowsers = Analytics::fetchTopBrowsers($period)
            ->map(fn ($item) => [
                'name' => $item[0] ?? 'Unknown',
                'sessions' => (int) ($item[1] ?? 0),
            ])
            ->toArray();

        // Top referrers
        $topReferrers = Analytics::fetchTopReferrers($period)
            ->map(fn ($item) => [
                'source' => $item[0] ?? 'Direct',
                'views' => (int) ($item[1] ?? 0),
            ])
            ->toArray();

        return [
            'type' => $this->reportType,
            'generated_at' => now()->toIso8601String(),
            'period' => [
                'start' => $period->startDate->toDateString(),
                'end' => $period->endDate->toDateString(),
            ],
            'overview' => [
                'sessions' => (int) ($overview['metric_0'] ?? 0),
                'users' => (int) ($overview['metric_1'] ?? 0),
                'pageviews' => (int) ($overview['metric_2'] ?? 0),
                'bounce_rate' => (float) ($overview['metric_3'] ?? 0),
            ],
            'top_pages' => $topPages,
            'top_browsers' => $topBrowsers,
            'top_referrers' => $topReferrers,
        ];
    }

    /**
     * Save report to storage
     */
    protected function saveReport(array $report): void
    {
        $timestamp = Carbon::parse($report['generated_at'])->format('Y-m-d_H-i-s');
        $filename = "analytics_report_{$this->reportType}_{$timestamp}.json";
        $path = storage_path("app/analytics-reports/{$filename}");

        // Crear directorio si no existe
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Send report email
     */
    protected function sendEmail(array $report): void
    {
        try {
            // Aquí iría la lógica de envío de email
            // Por ahora solo registramos que se enviaría
            Log::info("Report email would be sent to: {$this->email}");
        } catch (\Exception $e) {
            Log::error('Failed to send analytics report email: ' . $e->getMessage());
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Analytics report job failed', [
            'type' => $this->reportType,
            'error' => $exception->getMessage(),
        ]);
    }
}
