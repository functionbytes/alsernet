<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Events\Event;

class UserUpdated extends Event
{
    use SerializesModels;

    public $customer;
    public $delayed;
    public function __construct($customer, $delayed = true)
    {
        $this->customer = $customer;
        $this->delayed = $delayed;
    }

    public function broadcastOn()
    {
        return [];
    }

}
