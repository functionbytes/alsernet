<?php

namespace App\Providers;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Listeners\Campaigns\GiftvoucherListener;
use App\Listeners\SendNewUserNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SendNewUserNotification::class,
        ],
        GiftvoucherCreated::class => [
            GiftvoucherListener::class,
        ],
    ];

    /**
     * Bootstrap any event bindings.
     */
    public function boot(): void
    {
        parent::boot(); // Asegura que las configuraciones del padre se ejecuten si es necesario
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
