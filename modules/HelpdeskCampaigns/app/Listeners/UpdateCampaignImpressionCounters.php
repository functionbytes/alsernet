<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Models\CampaignVariant;

/**
 * Maintains denormalized impression/click counters on the parent campaign
 * row so the index view can read counts without joining helpdesk_campaign_impressions.
 * When the impression belongs to an A/B variant, the variant's own counters are
 * kept in sync too so its CTR accessor (clicks/impressions) is meaningful.
 * Runs on the impressions queue so it doesn't slow down the public tracking endpoint.
 */
class UpdateCampaignImpressionCounters implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 5;

    public int $backoff = 10;

    public function viaQueue(): string
    {
        return 'impressions';
    }

    public function handle(CampaignImpressionRecorded $event): void
    {
        $impression = $event->impression;
        $campaign = $impression->campaign;

        if (! $campaign) {
            return;
        }

        $column = $event->wasClick ? 'clicks_count' : 'impressions_count';

        $campaign->increment($column);

        if ($impression->variant_id) {
            CampaignVariant::whereKey($impression->variant_id)->increment($column);
        }
    }

    public function failed(CampaignImpressionRecorded $event, \Throwable $exception): void
    {
        Log::warning('Campaign counter update failed', [
            'impression_id' => $event->impression->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
