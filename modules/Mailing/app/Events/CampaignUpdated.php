<?php

namespace Modules\Mailing\Events;

use Illuminate\Queue\SerializesModels;

class CampaignUpdated extends Event
{
    use SerializesModels;

    public $campaign;

    public $delayed;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($campaign, $delayed = true)
    {
        $this->campaign = $campaign;
        $this->delayed = $delayed;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
