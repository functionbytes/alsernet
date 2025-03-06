<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\CampaignUpdated;
use App\Jobs\UpdateCampaignJob;

class CampaignUpdatedListener
{

    public function __construct()
    {
    }

    public function handle(CampaignUpdated $event)
    {
        if ($event->delayed) {
            dispatch(new UpdateCampaignJob($event->campaign));
        } else {
            $event->campaign->updateCache();
        }
    }

}
