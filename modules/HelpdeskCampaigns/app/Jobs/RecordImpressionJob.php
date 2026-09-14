<?php

namespace Modules\HelpdeskCampaigns\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Http\Controllers\Public\ImpressionTrackingController;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;
use Modules\HelpdeskCampaigns\Services\FrequencyCapService;

/**
 * Async impression tracking. The public tracking endpoint dispatches this
 * job so the HTTP response returns in <50ms even when traffic is heavy and
 * the helpdesk_campaign_impressions table is large.
 *
 * @see ImpressionTrackingController
 */
class RecordImpressionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly int $campaignId,
        private readonly array $payload,
    ) {
        $this->onQueue('impressions');
    }

    public function handle(): void
    {
        $campaign = Campaign::query()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $impression = CampaignImpression::create([
            'campaign_id' => $campaign->id,
            'variant_id' => $this->payload['variant_id'] ?? null,
            'impression_id' => $this->payload['impression_id'],
            'customer_id' => $this->payload['customer_id'] ?? null,
            'customer_session_id' => $this->payload['customer_session_id'] ?? null,
            'page_url' => $this->payload['page_url'] ?? null,
            'device_type' => $this->payload['device_type'] ?? null,
            'browser' => $this->payload['browser'] ?? null,
            'ip_address' => $this->payload['ip_address'] ?? null,
            'country' => $this->payload['country'] ?? null,
            'viewed_at' => $this->payload['viewed_at'] ?? now(),
            'metadata' => $this->payload['metadata'] ?? [],
        ]);

        CampaignImpressionRecorded::dispatch($impression);

        app(FrequencyCapService::class)->invalidate($campaign->id, [
            'customer_id' => $this->payload['customer_id'] ?? null,
            'customer_session_id' => $this->payload['customer_session_id'] ?? null,
            'ip_address' => $this->payload['ip_address'] ?? null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to record campaign impression', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
