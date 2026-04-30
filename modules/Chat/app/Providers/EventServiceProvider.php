<?php

namespace Modules\Chat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Chat\Events\ConversationAssigned;
use Modules\Chat\Events\ConversationCreated;
use Modules\Chat\Events\ConversationStatusChanged;
use Modules\Chat\Events\ConversationUpdated;
use Modules\Chat\Events\LowCsatRatingReceivedEvent;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Listeners\AssignConversationNotificationListener;
use Modules\Chat\Listeners\CreateLowCsatAlertListener;
use Modules\Chat\Listeners\DispatchWebhooksListener;
use Modules\Chat\Listeners\NotifyAgentsListener;
use Modules\Chat\Listeners\SendAutomationRulesListener;
use Modules\Chat\Listeners\SendConversationNotificationListener;
use Modules\Chat\Listeners\UpdateConversationStatusListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        MessageSent::class => [
            SendConversationNotificationListener::class,
        ],

        ConversationCreated::class => [
            SendAutomationRulesListener::class.'@handleConversationCreated',
            DispatchWebhooksListener::class.'@handleConversationCreated',
        ],

        ConversationStatusChanged::class => [
            UpdateConversationStatusListener::class,
            SendAutomationRulesListener::class.'@handleConversationStatusChanged',
            DispatchWebhooksListener::class.'@handleConversationStatusChanged',
        ],

        ConversationAssigned::class => [
            NotifyAgentsListener::class,
            AssignConversationNotificationListener::class,
            SendAutomationRulesListener::class.'@handleConversationAssigned',
            DispatchWebhooksListener::class.'@handleConversationAssigned',
        ],

        ConversationUpdated::class => [
            SendAutomationRulesListener::class.'@handleConversationUpdated',
            DispatchWebhooksListener::class.'@handleConversationUpdated',
        ],

        LowCsatRatingReceivedEvent::class => [
            CreateLowCsatAlertListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     */
    protected static $shouldDiscoverEvents = false;
}
