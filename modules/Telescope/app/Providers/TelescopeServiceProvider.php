<?php

namespace Modules\Telescope\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/telescope.php',
            'telescope'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish migrations from this module
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'telescope-migrations');

        // Configure authorization for Telescope access
        $this->configureAuthorization();

        // Only load Telescope in development or if explicitly enabled
        if (! $this->app->environment('production')) {
            Telescope::night();
        }

        // Filter entries to avoid storing too much data
        $this->filterEntries();

        // Configure watchers
        $this->configureWatchers();

        // Register routes
        $this->registerRoutes();
    }

    /**
     * Configure authorization for Telescope access
     */
    private function configureAuthorization(): void
    {
        // Allow all local requests to access Telescope
        Telescope::auth(function ($request) {
            // In development, allow all requests
            if ($this->app->environment('local', 'development')) {
                return true;
            }

            // In other environments, check for explicit authorization
            // You can add custom logic here (e.g., check IP, user role, etc.)
            return false;
        });
    }

    /**
     * Filter Telescope entries to control what gets recorded
     */
    private function filterEntries(): void
    {
        Telescope::filter(function (IncomingEntry $entry) {
            $type = $entry->type;

            // Skip static asset requests
            if ($type === 'request') {
                $uri = $entry->content['request']['uri'] ?? '';

                // Skip static assets
                if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|webp|ico|woff|woff2|ttf|eot)$/i', $uri)) {
                    return false;
                }

                // Skip health checks and ping routes
                if (preg_match('/\/(health|ping|livewire|telescope)/', $uri)) {
                    return false;
                }
            }

            // Skip recording of certain commands
            if ($type === 'command') {
                $command = $entry->content['command'] ?? '';

                // Skip long-running queue workers and schedulers
                if (preg_match('/queue:work|schedule:run|horizon/', $command)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Configure which watchers to enable
     */
    private function configureWatchers(): void
    {
        // Telescope watchers are configured via telescope config file
        // This method is here for future extensibility
    }

    /**
     * Register Telescope routes
     */
    private function registerRoutes(): void
    {
        if (! $this->app->environment('production')) {
            // Load Telescope routes automatically
            Telescope::startRecording();
        }
    }
}
