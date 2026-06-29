<?php

namespace Modules\Mailrelay\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Mailrelay\Events\CampaignSent;
use Modules\Mailrelay\Events\EmailValidated;
use Modules\Mailrelay\Events\ImportCompleted;
use Modules\Mailrelay\Events\SubscriberCreated;
use Modules\Mailrelay\Listeners\NotifyImportCompletion;
use Modules\Mailrelay\Listeners\SendCampaignAnalytics;
use Modules\Mailrelay\Listeners\SyncNewSubscriber;
use Modules\Mailrelay\Listeners\UpdateSubscriberValidationStatus;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CampaignSent::class => [
            SendCampaignAnalytics::class,
        ],
        SubscriberCreated::class => [
            SyncNewSubscriber::class,
        ],
        ImportCompleted::class => [
            NotifyImportCompletion::class,
        ],
        EmailValidated::class => [
            UpdateSubscriberValidationStatus::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
