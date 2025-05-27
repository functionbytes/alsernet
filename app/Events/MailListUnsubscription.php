<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use App\Models\Subscriber\Subscriber;
use Illuminate\Broadcasting\Channel;

class MailListUnsubscription
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $subscriber;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

}
