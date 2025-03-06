<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Events\Event;

class CampaignUpdated extends Event
{
    use SerializesModels;

    public $campaign;
    public $delayed;

    public function __construct($campaign, $delayed = true)
    {
        $this->campaign = $campaign;
        $this->delayed = $delayed;
    }

    public function broadcastOn()
    {
        return [];
    }

}
