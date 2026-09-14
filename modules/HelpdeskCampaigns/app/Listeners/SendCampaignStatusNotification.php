<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Notifications\CampaignEndedNotification;
use Modules\HelpdeskCampaigns\Notifications\CampaignPublishedNotification;

/**
 * Notifies admins/managers about important campaign status transitions.
 * Hooks into CampaignPublished and CampaignEnded only — paused/resumed are
 * routine and would create notification noise.
 */
class SendCampaignStatusNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(CampaignPublished|CampaignEnded $event): void
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'super-settings']))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = $event instanceof CampaignPublished
            ? new CampaignPublishedNotification($event->campaign)
            : new CampaignEndedNotification($event->campaign);

        Notification::send($recipients, $notification);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('Campaign status notification failed', [
            'event' => $event::class,
            'campaign_id' => $event->campaign->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
