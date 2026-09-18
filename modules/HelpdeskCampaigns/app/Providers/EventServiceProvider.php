<?php

namespace Modules\HelpdeskCampaigns\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;
use Modules\HelpdeskCampaigns\Listeners\DispatchCampaignWebhooks;
use Modules\HelpdeskCampaigns\Listeners\LogCampaignActivity;
use Modules\HelpdeskCampaigns\Listeners\SendCampaignStatusNotification;
use Modules\HelpdeskCampaigns\Listeners\UpdateCampaignImpressionCounters;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CampaignPublished::class => [
            LogCampaignActivity::class,
            SendCampaignStatusNotification::class,
            DispatchCampaignWebhooks::class,
        ],
        CampaignPaused::class => [
            LogCampaignActivity::class,
            DispatchCampaignWebhooks::class,
        ],
        CampaignResumed::class => [
            LogCampaignActivity::class,
            DispatchCampaignWebhooks::class,
        ],
        CampaignEnded::class => [
            LogCampaignActivity::class,
            SendCampaignStatusNotification::class,
            DispatchCampaignWebhooks::class,
        ],
        CampaignImpressionRecorded::class => [
            UpdateCampaignImpressionCounters::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
