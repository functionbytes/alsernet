<?php

namespace Modules\HelpdeskCampaigns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskCampaigns\Models\Campaign;

class CampaignPaused
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Campaign $campaign) {}
}
