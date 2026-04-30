<?php

namespace Modules\Campaign\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignClickLog;

class CampaignMessageClicked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly CampaignClickLog $clickLog,
    ) {}
}
