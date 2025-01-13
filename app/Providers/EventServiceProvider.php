<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Listeners\Auth\Password\ForgotPasswordListener;
use App\Listeners\Auth\Password\ResetPasswordListener;
use App\Events\Auth\Password\ForgotPasswordCreated;
use App\Events\Auth\Password\ResetPasswordCreated;
use App\Listeners\Orders\OrderListener;
use Illuminate\Auth\Events\Registered;
use App\Events\Orders\OrderCreated;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

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
