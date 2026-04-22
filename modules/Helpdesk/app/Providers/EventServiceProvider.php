<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Listeners\BroadcastConversationMessage;
use Modules\Helpdesk\Listeners\DispatchConversationWebhooks;
use Modules\Helpdesk\Listeners\LogConversationCreated;
use Modules\Helpdesk\Listeners\LogConversationUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConversationCreated::class => [
            LogConversationCreated::class,
            DispatchConversationWebhooks::class,
        ],
        ConversationMessageCreated::class => [
            BroadcastConversationMessage::class,
        ],
        ConversationUpdated::class => [
            LogConversationUpdated::class,
            DispatchConversationWebhooks::class,
        ],
    ];
}
