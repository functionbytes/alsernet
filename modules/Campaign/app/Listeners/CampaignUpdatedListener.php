<?php

namespace Modules\Campaign\Listeners;

use Modules\Campaign\Events\CampaignUpdated;
use Modules\Campaign\Jobs\UpdateCampaignJob;

class CampaignUpdatedListener
{
    public function __construct() {}

    public function handle(CampaignUpdated $event)
    {
        if ($event->delayed) {
            dispatch(new UpdateCampaignJob($event->campaign));
        } else {
            $event->campaign->updateCache();
        }
    }
}
