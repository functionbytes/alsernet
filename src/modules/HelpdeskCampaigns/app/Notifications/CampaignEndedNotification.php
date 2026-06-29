<?php

namespace Modules\HelpdeskCampaigns\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskCampaigns\Models\Campaign;

class CampaignEndedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Campaign $campaign) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        $impressions = $this->campaign->impressions_count ?? 0;
        $clicks = $this->campaign->clicks_count ?? 0;

        return [
            'type' => 'helpdeskcampaigns_campaign_ended',
            'title' => 'Campaña finalizada',
            'message' => "La campaña '{$this->campaign->name}' ha finalizado. {$impressions} impresiones · {$clicks} clics.",
            'entity_id' => $this->campaign->id,
            'action_url' => route('helpdesk.campaigns.statistics', $this->campaign),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
