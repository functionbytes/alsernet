<?php

namespace Modules\HelpdeskTranslate\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\HelpdeskTranslate\Listeners\TranslateIncomingMessage;
use Modules\HelpdeskTranslate\Listeners\TranslateOutgoingMessage;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event => listener bindings registered by this module.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected array $listen = [
        MessageReceived::class => [
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
