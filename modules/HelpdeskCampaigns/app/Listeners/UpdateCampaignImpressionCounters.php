<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;

/**
 * Maintains denormalized impression/click counters on the parent campaign
 * row so the index view can read counts without joining helpdesk_campaign_impressions.
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
        $campaign = $event->impression->campaign;

        if (! $campaign) {
            return;
        }

        if (! $event->wasClick) {
            $campaign->increment('impressions_count');
        } else {
            $campaign->increment('clicks_count');
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
