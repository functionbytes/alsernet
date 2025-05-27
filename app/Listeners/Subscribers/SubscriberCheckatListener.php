<?php

namespace App\Listeners\Subscribers;

use App\Events\Subscribers\SubscriberCheckatEvent;
use App\Jobs\Subscribers\SubscriberCheckatJob;
use Illuminate\Support\Facades\Log;

class SubscriberCheckatListener
{
    public function handle(SubscriberCheckatEvent $event): void
    {
        Log::info("Ejecutando SubscriberCheckatListener para {$event->subscriber->email}");
        SubscriberCheckatJob::dispatch($event->subscriber);
    }
}
