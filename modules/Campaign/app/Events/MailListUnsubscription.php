<?php

namespace Modules\Campaign\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Entities\CampaignMaillistsSubscriber;

/**
 * Event triggered when a subscriber unsubscribes from a mail list.
 *
 * This event is dispatched whenever a subscriber unsubscribes from a mail list.
 * It can be listened to by event listeners for logging, notifications, or other actions.
 */
class MailListUnsubscription
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CampaignMaillistsSubscriber $subscriber The subscriber that unsubscribed
     */
    public function __construct(public CampaignMaillistsSubscriber $subscriber)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('subscribers'),
        ];
    }
}
