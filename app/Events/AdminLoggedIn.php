<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdminLoggedIn extends Event
{
    use SerializesModels;

    protected $admin;

    public function __construct($admin = null)
    {
        $this->admin = $admin;
    }

    public function broadcastOn()
    {
        return [];
    }

}
