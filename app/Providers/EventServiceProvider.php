<?php

namespace App\Providers;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Listeners\Campaigns\GiftvoucherListener;
use App\Listeners\SendNewUserNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SendNewUserNotification::class,
        ],
        GiftvoucherCreated::class => [
            GiftvoucherListener::class,
        ],
    ];
    public function boot(): void
    {

    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

}
