<?php

namespace Modules\HelpdeskCampaigns\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

/**
 * Scheduled job that prunes old impression rows. Retention defaults to 180
 * days but can be tuned via config('helpdeskcampaigns.impressions_retention_days').
 * Aggregated counters live on the parent campaign so deleting raw rows
 * does not lose total counts.
 */
class CleanupOldImpressionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('impressions');
    }

    public function handle(): void
    {
        $days = (int) config('helpdeskcampaigns.impressions_retention_days', 180);
        $cutoff = now()->subDays($days);
        $total = 0;

        // Delete in chunks to avoid long-running table locks
        do {
            $deleted = CampaignImpression::query()
                ->where('viewed_at', '<', $cutoff)
                ->limit(5000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        Log::info('Cleaned up old campaign impressions', [
            'deleted' => $total,
            'cutoff' => $cutoff->toIso8601String(),
        ]);
    }
}
