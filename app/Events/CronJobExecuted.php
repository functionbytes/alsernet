<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Events\Event;

class CronJobExecuted extends Event
{
    use SerializesModels;
    public function __construct()
    {
        return;
    }

    public function broadcastOn()
    {
        return [];
    }
}
