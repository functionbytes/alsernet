<?php

namespace Modules\Campaign\Events;

use Illuminate\Queue\SerializesModels;

class CronJobExecuted extends Event
{
    use SerializesModels;

    public function __construct() {}

    public function broadcastOn()
    {
        return [];
    }
}
