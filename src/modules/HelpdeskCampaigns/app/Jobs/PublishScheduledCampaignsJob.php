<?php

namespace Modules\HelpdeskCampaigns\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * Activates scheduled campaigns whose published_at has arrived but whose
 * status is still 'scheduled'. Runs every minute via the scheduler.
 */
class PublishScheduledCampaignsJob implements ShouldQueue
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
        $due = Campaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('approval_required')
                    ->orWhere('approval_required', false)
                    ->orWhereNotNull('approved_at');
            })
            ->limit(100)
            ->get();

        foreach ($due as $campaign) {
            $campaign->update(['status' => 'active']);
            CampaignPublished::dispatch($campaign);
        }

        if ($due->isNotEmpty()) {
            Log::info('Auto-published scheduled campaigns', ['count' => $due->count()]);
        }

        if ($due->count() === 100) {
            Log::warning('PublishScheduledCampaignsJob hit its 100-row cap; more scheduled campaigns may be due and will wait for the next run.');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishScheduledCampaignsJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
