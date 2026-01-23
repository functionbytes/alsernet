<?php

namespace Modules\HelpdeskChat\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskChat';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * Routes are registered by HelpdeskChatServiceProvider with appropriate
     * middleware, prefixes, and naming conventions. This provider is kept
     * minimal to avoid route duplication and conflicts.
     */
    public function map(): void
    {
        // Routes are loaded by HelpdeskChatServiceProvider::registerRoutes()
    }
}
