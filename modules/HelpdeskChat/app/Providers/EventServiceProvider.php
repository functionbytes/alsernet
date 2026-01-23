<?php

namespace Modules\HelpdeskChat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HelpdeskChat\Events\ConversationAssigned;
use Modules\HelpdeskChat\Events\ConversationStatusChanged;
use Modules\HelpdeskChat\Events\LowCsatRatingReceivedEvent;
use Modules\HelpdeskChat\Events\MessageSent;
use Modules\HelpdeskChat\Listeners\AssignConversationNotificationListener;
use Modules\HelpdeskChat\Listeners\CreateLowCsatAlertListener;
use Modules\HelpdeskChat\Listeners\SendConversationNotificationListener;
use Modules\HelpdeskChat\Listeners\UpdateConversationStatusListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        MessageSent::class => [
            SendConversationNotificationListener::class,
        ],
        ConversationStatusChanged::class => [
            UpdateConversationStatusListener::class,
        ],
        ConversationAssigned::class => [
            AssignConversationNotificationListener::class,
        ],
        LowCsatRatingReceivedEvent::class => [
            CreateLowCsatAlertListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Get the discovery paths for automatic event discovery.
     */
    protected function discoverEventsWithin(): array
    {
        return [
            module_path('Chat', 'app/Listeners'),
        ];
    }
}
