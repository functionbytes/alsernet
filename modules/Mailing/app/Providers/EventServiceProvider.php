<?php

namespace Modules\Mailing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Modules\Mailing\Events\CampaignUpdated' => [
            'Modules\Mailing\Listeners\CampaignUpdatedListener',
        ],
        'Modules\Mailing\Events\MailListUpdated' => [
            'Modules\Mailing\Listeners\MailListUpdatedListener',
        ],
        'Modules\Mailing\Events\UserUpdated' => [
            'Modules\Mailing\Listeners\UserUpdatedListener',
        ],
        'Modules\Mailing\Events\CronJobExecuted' => [
            'Modules\Mailing\Listeners\CronJobExecutedListener',
        ],
        'Modules\Mailing\Events\AdminLoggedIn' => [
            'Modules\Mailing\Listeners\AdminLoggedInListener',
        ],
        'Modules\Mailing\Events\MailListSubscription' => [
            /* Use subscriber instead */
            // 'Modules\Mailing\Listeners\SendListNotificationToOwner',
            // 'Modules\Mailing\Listeners\SendListNotificationToSubscriber',
            // 'Modules\Mailing\Listeners\TriggerAutomation',
        ],
        'Modules\Mailing\Events\MailListUnsubscription' => [
            /* Use subscriber instead */
            // 'Modules\Mailing\Listeners\SendListNotificationToOwner',
            // 'Modules\Mailing\Listeners\SendListNotificationToSubscriber',
            // 'Modules\Mailing\Listeners\TriggerAutomation',
        ],
        'Modules\Mailing\Events\MailListImported' => [
            'Modules\Mailing\Listeners\TriggerAutomationForImportedContacts',
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        'Modules\Mailing\Listeners\TriggerAutomation',
        'Modules\Mailing\Listeners\SendListNotificationToOwner',
        'Modules\Mailing\Listeners\SendListNotificationToSubscriber',
    ];

    /**
     * Register any events for your application.
     */
    public function boot()
    {
        parent::boot();
    }
}
