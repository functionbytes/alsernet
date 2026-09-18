<?php

namespace Modules\HelpdeskCampaigns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

class CampaignImpressionRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CampaignImpression $impression,
        public readonly bool $wasClick = false,
    ) {}
}
