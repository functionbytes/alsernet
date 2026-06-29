<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\CampaignAnalytics;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Services\ProviderManager;

/**
 * Syncs campaign statistics from mail providers periodically.
 *
 * Queries all SENT campaigns with a provider_campaign_id that haven't
 * been synced in the last 30 minutes and updates their analytics.
 */
class SyncCampaignStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Execute the job.
     */
    public function handle(ProviderManager $providerManager): void
    {
        $campaigns = Campaign::query()
            ->where('status', CampaignStatus::SENT)
            ->whereNotNull('provider_campaign_id')
            ->where(function ($query) {
                $query->whereDoesntHave('analytics')
                    ->orWhereHas('analytics', function ($q) {
                        $q->where('last_synced_at', '<', now()->subMinutes(30))
                            ->orWhereNull('last_synced_at');
                    });
            })
            ->get();

        if ($campaigns->isEmpty()) {
            Log::info('SyncCampaignStats: no campaigns need syncing');

            return;
        }

        Log::info('SyncCampaignStats: syncing stats for campaigns', [
            'count' => $campaigns->count(),
        ]);

        $synced = 0;
        $failed = 0;

        foreach ($campaigns as $campaign) {
            try {
                $provider = $providerManager->byId($campaign->mail_provider_id);
                $stats = $provider->getStats($campaign->provider_campaign_id);

                $analytics = $campaign->analytics ?? new CampaignAnalytics([
                    'campaign_id' => $campaign->id,
                ]);

                $analytics->updateFromMailrelay($stats);

                $synced++;

                Log::debug('SyncCampaignStats: campaign synced', [
                    'campaign_id' => $campaign->id,
                    'provider_campaign_id' => $campaign->provider_campaign_id,
                ]);
            } catch (\Exception $e) {
                $failed++;

                Log::error('SyncCampaignStats: failed to sync campaign', [
                    'campaign_id' => $campaign->id,
                    'provider_campaign_id' => $campaign->provider_campaign_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SyncCampaignStats: completed', [
            'total' => $campaigns->count(),
            'synced' => $synced,
            'failed' => $failed,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SyncCampaignStats: job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
