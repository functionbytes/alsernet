<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;

/**
 * Logs campaign lifecycle transitions to both the standard log channel and
 * the activity log table when spatie/activitylog is available on the model.
 */
class LogCampaignActivity implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(CampaignPublished|CampaignPaused|CampaignResumed|CampaignEnded $event): void
    {
        $action = match (true) {
            $event instanceof CampaignPublished => 'published',
            $event instanceof CampaignPaused => 'paused',
            $event instanceof CampaignResumed => 'resumed',
            $event instanceof CampaignEnded => 'ended',
        };

        Log::info("Campaign {$action}", [
            'campaign_id' => $event->campaign->id,
            'name' => $event->campaign->name,
            'status' => $event->campaign->status,
            'user_id' => auth()->id(),
        ]);

        if (method_exists($event->campaign, 'activities')) {
            try {
                activity('helpdesk-campaigns')
                    ->performedOn($event->campaign)
                    ->causedBy(auth()->user())
                    ->withProperties(['action' => $action])
                    ->log("campaign.{$action}");
            } catch (\Throwable $e) {
                // activitylog package not bound — silently skip
            }
        }
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('Campaign activity log failed', [
            'event' => $event::class,
            'campaign_id' => $event->campaign->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
