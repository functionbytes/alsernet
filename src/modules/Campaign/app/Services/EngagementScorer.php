<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\SubscriberEngagementScore;

/**
 * Calcula y actualiza el engagement score de cada suscriptor
 * basado en opens, clicks, bounces y envíos de los últimos 30-90 días.
 */
class EngagementScorer
{
    public function scoreSubscriber(CampaignSubscriber $subscriber): SubscriberEngagementScore
    {
        $sent30d = DB::table('campaign_tracking_logs')
            ->where('subscriber_id', $subscriber->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $opens30d = DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->where('campaign_open_logs.created_at', '>=', now()->subDays(30))
            ->distinct('campaign_open_logs.tracking_log_id')
            ->count('campaign_open_logs.tracking_log_id');

        $clicks30d = DB::table('campaign_click_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_click_logs.tracking_log_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->where('campaign_click_logs.created_at', '>=', now()->subDays(30))
            ->distinct('campaign_click_logs.tracking_log_id')
            ->count('campaign_click_logs.tracking_log_id');

        $bounces90d = DB::table('campaign_tracking_logs')
            ->where('subscriber_id', $subscriber->id)
            ->where('status', 'bounced')
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        $lastOpen = DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->latest('campaign_open_logs.created_at')
            ->value('campaign_open_logs.created_at');

        $lastClick = DB::table('campaign_click_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_click_logs.tracking_log_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->latest('campaign_click_logs.created_at')
            ->value('campaign_click_logs.created_at');

        // Fórmula: base 50, +20 por tasa de apertura, +30 por tasa de clic, -30 por rebote
        $openRate = $sent30d > 0 ? ($opens30d / $sent30d) : 0;
        $clickRate = $sent30d > 0 ? ($clicks30d / $sent30d) : 0;

        $score = (int) round(
            50
            + ($openRate * 20)
            + ($clickRate * 30)
            - (min(1, $bounces90d / max(1, $sent30d)) * 30)
            - (now()->diffInDays($lastOpen ?? now()->subYear()) * 0.5)
        );

        $score = max(-100, min(100, $score));

        return SubscriberEngagementScore::updateOrCreate(
            ['subscriber_id' => $subscriber->id],
            [
                'score' => $score,
                'opens_30d' => $opens30d,
                'clicks_30d' => $clicks30d,
                'sent_30d' => $sent30d,
                'bounces_90d' => $bounces90d,
                'last_opened_at' => $lastOpen,
                'last_clicked_at' => $lastClick,
            ]
        );
    }

    public function scoreBatch(int $limit = 1000): int
    {
        $processed = 0;
        CampaignSubscriber::query()
            ->select('id')
            ->chunkById($limit, function ($subscribers) use (&$processed): void {
                foreach ($subscribers as $subscriber) {
                    $this->scoreSubscriber($subscriber);
                    $processed++;
                }
            });

        return $processed;
    }
}
