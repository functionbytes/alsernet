<?php

namespace Modules\HelpdeskCampaigns\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * Ends campaigns whose ends_at has passed OR whose goal has been reached.
 * Runs every minute via the scheduler.
 */
class EndExpiredCampaignsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('campaigns-scheduler');
    }

    public function handle(): void
    {
        // Time-based expiry
        $expired = Campaign::query()
            ->whereIn('status', ['active', 'paused'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->limit(100)
            ->get();

        $expiredEnded = 0;

        foreach ($expired as $campaign) {
            $transitioned = Campaign::query()
                ->whereKey($campaign->id)
                ->whereIn('status', ['active', 'paused'])
                ->update(['status' => 'ended']);

            if ($transitioned !== 1) {
                continue;
            }

            $expiredEnded++;
            CampaignEnded::dispatch($campaign->refresh());
        }

        // Goal-based auto-end — comparison done in SQL using denormalized counters
        $goalReached = Campaign::query()
            ->where('status', 'active')
            ->whereNotNull('goal_type')
            ->whereNotNull('goal_value')
            ->where('goal_value', '>', 0)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('goal_type', 'impressions')->whereColumn('impressions_count', '>=', 'goal_value'))
                ->orWhere(fn ($q) => $q->where('goal_type', 'clicks')->whereColumn('clicks_count', '>=', 'goal_value'))
            )
            ->limit(100)
            ->get();

        $goalEnded = 0;

        foreach ($goalReached as $campaign) {
            $transitioned = Campaign::query()
                ->whereKey($campaign->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'ends_at' => now(),
                ]);

            if ($transitioned !== 1) {
                continue;
            }

            $goalEnded++;
            CampaignEnded::dispatch($campaign->refresh());
        }

        $total = $expiredEnded + $goalEnded;
        if ($total > 0) {
            Log::info('Auto-ended campaigns', [
                'time_based' => $expiredEnded,
                'goal_based' => $goalEnded,
            ]);
        }

        // Either batch filling its 100-row cap means more may be due this tick.
        if ($expired->count() === 100 || $goalReached->count() === 100) {
            Log::warning('EndExpiredCampaignsJob hit its 100-row cap; more campaigns may need ending and will wait for the next run.', [
                'time_based' => $expired->count(),
                'goal_based' => $goalReached->count(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EndExpiredCampaignsJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
