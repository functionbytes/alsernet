<?php

namespace Modules\Analytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Analytics\Events\ReportGenerated;
use Modules\Analytics\Mail\AnalyticsReportMail;
use Modules\Analytics\Models\AnalyticsReportSchedule;
use Modules\Analytics\Period;
use Modules\Analytics\Services\AnalyticsReportService;

class GenerateAnalyticsReport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param  string  $reportType  'daily', 'weekly', 'monthly' (used for ad-hoc reports)
     * @param  string|null  $email  Email destination (ad-hoc reports only)
     * @param  AnalyticsReportSchedule|null  $schedule  Persisted schedule record
     */
    public function __construct(
        protected string $reportType = 'daily',
        protected ?string $email = null,
        protected ?AnalyticsReportSchedule $schedule = null,
    ) {
        if ($schedule) {
            $this->reportType = $schedule->frequency;
            $this->email = $schedule->email;
        }
    }

    /**
     * Dispatch the job for a persisted schedule record.
     */
    public static function dispatchForSchedule(AnalyticsReportSchedule $schedule): void
    {
        static::dispatch(schedule: $schedule);
    }

    /**
     * Unique ID per schedule. Ad-hoc jobs (no schedule) have no uniqueness constraint.
     */
    public function uniqueId(): string
    {
        if (! $this->schedule) {
            return '';
        }

        return "analytics_report_{$this->schedule->id}";
    }

    /**
     * Keep the unique lock for 5 minutes.
     */
    public function uniqueFor(): int
    {
        return 300;
    }

    public function handle(AnalyticsReportService $service): void
    {
        try {
            if (! setting('google_analytics_property_id') || ! setting('google_analytics_credentials')) {
                Log::warning('Analytics not configured. Skipping report generation.');

                return;
            }

            $period = $this->getPeriodByType();
            $report = $service->generateReport($period, $this->reportType);

            $service->saveReport($report, $this->reportType);

            if ($this->schedule) {
                $this->sendScheduledEmail($report, $service);
            } elseif ($this->email) {
                $this->sendEmail($report);
            }

            Log::info(__('analytics::analytics.success.report_generated'), [
                'type' => $this->reportType,
                'schedule_id' => $this->schedule?->id,
                'email' => $this->email,
            ]);

            event(new ReportGenerated($report, $this->schedule));
        } catch (\Exception $e) {
            Log::error('Analytics report generation failed: '.$e->getMessage(), [
                'schedule_id' => $this->schedule?->id,
            ]);

            throw $e;
        }
    }

    public function failed(\Exception $exception): void
    {
        Log::error('Analytics report job failed', [
            'type' => $this->reportType,
            'schedule_id' => $this->schedule?->id,
            'error' => $exception->getMessage(),
        ]);
    }

    protected function getPeriodByType(): Period
    {
        return match ($this->reportType) {
            'weekly' => Period::days(7),
            'monthly' => Period::days(30),
            default => Period::days(1),
        };
    }

    protected function sendScheduledEmail(array $report, AnalyticsReportService $service): void
    {
        $reportPath = null;

        try {
            $reportPath = $service->generateReportFile($report, $this->schedule->format);
            $summary = $service->buildSummary($report);

            Mail::to($this->schedule->email)
                ->send(new AnalyticsReportMail($this->schedule, $reportPath, $summary));

            $this->schedule->update([
                'last_sent_at' => now(),
                'next_run_at' => $service->calculateNextRun($this->schedule->frequency),
            ]);

            Log::info(__('analytics::analytics.success.report_sent'), [
                'schedule_id' => $this->schedule->id,
                'email' => $this->schedule->email,
                'format' => $this->schedule->format,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send scheduled analytics report: '.$e->getMessage(), [
                'schedule_id' => $this->schedule->id,
            ]);
        } finally {
            if ($reportPath && file_exists($reportPath)) {
                @unlink($reportPath);
            }
        }
    }

    protected function sendEmail(array $report): void
    {
        try {
            Mail::send(
                [],
                [],
                function ($message) use ($report) {
                    $subject = match ($this->reportType) {
                        'weekly' => __('analytics::analytics.reports.subject_weekly'),
                        'monthly' => __('analytics::analytics.reports.subject_monthly'),
                        default => __('analytics::analytics.reports.subject_daily'),
                    };

                    $message->to($this->email)
                        ->subject($subject)
                        ->html($this->buildEmailBody($report));
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send analytics report email: '.$e->getMessage());
        }
    }

    protected function buildEmailBody(array $report): string
    {
        $period = $report['period']['start'].' → '.$report['period']['end'];
        $sessions = number_format($report['overview']['sessions'] ?? 0);
        $users = number_format($report['overview']['users'] ?? 0);
        $pageviews = number_format($report['overview']['pageviews'] ?? 0);
        $bounceRate = round(($report['overview']['bounce_rate'] ?? 0) * 100, 1);

        $topPagesRows = '';

        foreach (array_slice($report['top_pages'] ?? [], 0, 5) as $page) {
            $topPagesRows .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #f0f0f0;'>{$page['title']}</td><td style='padding:6px 12px;border-bottom:1px solid #f0f0f0;text-align:right;'>{$page['views']}</td></tr>";
        }

        $titleText = __('analytics::analytics.reports.title');
        $periodLabel = __('analytics::analytics.reports.period');
        $sessionsLabel = __('analytics::analytics.reports.sessions');
        $usersLabel = __('analytics::analytics.reports.users');
        $pageviewsLabel = __('analytics::analytics.reports.pageviews');
        $bounceRateLabel = __('analytics::analytics.reports.bounce_rate');
        $topPagesLabel = __('analytics::analytics.reports.top_pages');
        $columnPage = __('analytics::analytics.reports.column_page');
        $columnViews = __('analytics::analytics.reports.column_views');
        $footerText = __('analytics::analytics.reports.footer');

        return <<<HTML
<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333;">
<h2 style="color:#13C672;border-bottom:2px solid #13C672;pb:10px;">{$titleText}</h2>
<p style="color:#666;">{$periodLabel}: <strong>{$period}</strong></p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0;">
<div style="background:#c8f7dc;padding:16px;border-radius:8px;text-align:center;">
  <div style="font-size:24px;font-weight:bold;">{$sessions}</div><div style="color:#666;font-size:12px;">{$sessionsLabel}</div>
</div>
<div style="background:#d4e8ff;padding:16px;border-radius:8px;text-align:center;">
  <div style="font-size:24px;font-weight:bold;">{$users}</div><div style="color:#666;font-size:12px;">{$usersLabel}</div>
</div>
<div style="background:#ffd8d8;padding:16px;border-radius:8px;text-align:center;">
  <div style="font-size:24px;font-weight:bold;">{$pageviews}</div><div style="color:#666;font-size:12px;">{$pageviewsLabel}</div>
</div>
<div style="background:#ffe5cc;padding:16px;border-radius:8px;text-align:center;">
  <div style="font-size:24px;font-weight:bold;">{$bounceRate}%</div><div style="color:#666;font-size:12px;">{$bounceRateLabel}</div>
</div>
</div>
<h3>{$topPagesLabel}</h3>
<table style="width:100%;border-collapse:collapse;">
<thead><tr style="background:#f9f9f9;"><th style="padding:8px 12px;text-align:left;">{$columnPage}</th><th style="padding:8px 12px;text-align:right;">{$columnViews}</th></tr></thead>
<tbody>{$topPagesRows}</tbody>
</table>
<p style="color:#999;font-size:12px;margin-top:30px;">{$footerText}</p>
</body></html>
HTML;
    }
}
