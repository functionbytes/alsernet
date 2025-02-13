<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailListUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $mailList;
    public $delayed;

    public function __construct($mailList, $delayed = true)
    {
        $this->mailList = $mailList;
        $this->delayed = $delayed;
    }

    public function broadcastOn()
    {
        return [];
    }
}
