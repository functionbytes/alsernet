<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\CampaignUpdated;
use Modules\Mailing\Jobs\UpdateCampaignJob;

class CampaignUpdatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CampaignUpdated $event)
    {
        if ($event->delayed) {
            dispatch(new UpdateCampaignJob($event->campaign));
        } else {
            // @deprecated
            $event->campaign->updateCache();
        }
    }
}
