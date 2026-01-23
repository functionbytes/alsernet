<?php

namespace Modules\Campaign\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Entities\CampaignMaillistsSubscriber;

/**
 * Event triggered when a subscriber subscribes to a mail list.
 *
 * This event is dispatched whenever a new subscriber successfully subscribes to a mail list.
 * It can be listened to by event listeners for logging, notifications, or other actions.
 */
class MailListSubscription
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CampaignMaillistsSubscriber $subscriber The subscriber that subscribed
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
