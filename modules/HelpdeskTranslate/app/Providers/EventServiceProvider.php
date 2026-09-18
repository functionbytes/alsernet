<?php

namespace Modules\HelpdeskTranslate\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\HelpdeskTranslate\Listeners\TranslateIncomingMessage;
use Modules\HelpdeskTranslate\Listeners\TranslateOutgoingMessage;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event => listener bindings registered by this module.
     *
     * ConversationMessageCreated is the event every channel dispatches when a
     * conversation item is created (WhatsApp/social via InboundMessageIngestor,
     * live chat widget, agent replies). MessageReceived is widget-only, so
     * listening on it left WhatsApp/social messages never auto-translated.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected array $listen = [
        ConversationMessageCreated::class => [
            TranslateIncomingMessage::class,
            TranslateOutgoingMessage::class,
        ],
    ];

    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
