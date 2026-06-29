<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Seo\Mail\SeoContentDecayMail;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\WebhookNotificationService;

/**
 * Detecta páginas con "content decay": no se actualizan hace tiempo
 * y/o cayeron en GSC (clicks/impresiones/posición).
 *
 * Se dispara semanalmente desde el scheduler y alerta por mail + webhook.
 */
class DetectContentDecayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('seo');
    }

    public function handle(): void
    {
        $config = config('seohelper.content_decay', []);

        $daysStale = (int) ($config['days_stale'] ?? 90);
        $minClicks = (int) ($config['min_clicks_baseline'] ?? 50);
        $positionDrop = (float) ($config['position_drop'] ?? 5.0);
        $maxReport = (int) ($config['max_report_items'] ?? 50);

        $cutoff = now()->subDays($daysStale);

        $decaying = SeoMeta::query()
            ->whereNotNull('canonical_url')
            ->where('updated_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%');
            })
            ->where(function ($q) use ($minClicks, $positionDrop) {
                $q->where('gsc_clicks', '>=', $minClicks)
                    ->orWhere('gsc_position', '>=', $positionDrop);
            })
            ->orderByDesc('gsc_clicks')
            ->limit($maxReport)
            ->get(['id', 'title', 'canonical_url', 'gsc_clicks', 'gsc_impressions', 'gsc_position', 'updated_at', 'seo_score']);

        if ($decaying->isEmpty()) {
            Log::info('DetectContentDecayJob: no decaying pages found');

            return;
        }

        Log::info('DetectContentDecayJob: detected decaying pages', [
            'count' => $decaying->count(),
            'days_stale' => $daysStale,
        ]);

        $email = config('Seo.notifications.email', config('mail.from.address'));

        if ($email) {
            Mail::to($email)->queue(new SeoContentDecayMail($decaying, $daysStale));
        }

        if (config('Seo.webhooks.notify_score_drop', true)) {
            (new WebhookNotificationService)->sendContentDecay($decaying, $daysStale);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DetectContentDecayJob failed', ['error' => $e->getMessage()]);
    }
}
