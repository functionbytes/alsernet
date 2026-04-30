<?php

namespace Modules\Campaign\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignOpenLog;

class CampaignMessageOpened
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly CampaignOpenLog $openLog,
    ) {}
}
