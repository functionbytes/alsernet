<?php

namespace Modules\Campaign\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Entities\Campaign;

/**
 * Event triggered when a campaign is updated.
 *
 * This event is dispatched whenever a campaign's properties are modified.
 * It can be listened to by event listeners for logging, notifications, or other actions.
 */
class CampaignUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Campaign  $campaign  The campaign that was updated
     */
    public function __construct(public Campaign $campaign) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('campaigns'),
        ];
    }
}
