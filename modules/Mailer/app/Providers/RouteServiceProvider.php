<?php

namespace Modules\Mailer\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Mailer';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * NOTE: Routes are now registered in MailerServiceProvider
     * This class is kept for compatibility but does not register routes
     */
    public function boot(): void
    {
        parent::boot();
    }
}
