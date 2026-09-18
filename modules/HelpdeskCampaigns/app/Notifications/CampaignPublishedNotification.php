<?php

namespace Modules\HelpdeskCampaigns\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskCampaigns\Models\Campaign;

class CampaignPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Campaign $campaign) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdeskcampaigns_campaign_published',
            'title' => 'Campaña publicada',
            'message' => "La campaña '{$this->campaign->name}' está activa.",
            'entity_id' => $this->campaign->id,
            'action_url' => route('helpdesk.campaigns.show', $this->campaign),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
