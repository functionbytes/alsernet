<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Notification\Console\Commands\CleanOldNotifications;
use Modules\Theme\Services\NavService;

class NotificationServiceProvider extends ServiceProvider
{
    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/notification.php',
            'notification'
        );

        // Register commands
        $this->registerCommands();
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/notification.php' => config_path('notification.php'),
        ], 'notification-config');

        // Publish assets (CSS and JavaScript) - use compiled versions from public/
        $this->publishes([
            __DIR__.'/../../public/css' => public_path('modules/Notification/css'),
            __DIR__.'/../../public/js' => public_path('modules/Notification/js'),
        ], 'notification-assets');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'notification');

        // Configure WebSocket/Pusher settings (deferred to avoid boot issues)
        $this->app->booted(function () {
            $this->configureWebSocketSettings();
        });

        // Register scheduled tasks
        $this->registerSchedules();

        // Register routes
        $this->registerRoutes();

        // Register menus
        $this->registerMenus();
    }

    /**
     * Register routes for the Notification module
     */
    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        // Notification operational routes
        Route::middleware(['web', 'auth:web'])
            ->prefix('notifications')
            ->name('notifications.')
            ->group(function () use ($modulePath) {
                require $modulePath.'/routes/web.php';
            });

        // Notification backups routes
        Route::middleware(['web', 'auth:web', 'role:super-admin'])
            ->prefix('settings/notifications')
            ->name('settings.notifications.')
            ->group(function () use ($modulePath) {
                require $modulePath.'/routes/settings.php';
            });

        // API routes
        Route::middleware(['web', 'auth:web'])
            ->prefix('api/notifications')
            ->name('api.notifications.')
            ->group(function () use ($modulePath) {
                require $modulePath.'/routes/api.php';
            });
    }

    protected function registerCommands(): void
    {
        $this->commands([
            CleanOldNotifications::class,
        ]);
    }

    /**
     * Configure WebSocket and Pusher settings from database
     */
    protected function configureWebSocketSettings(): void
    {
        $settings = $this->getSettings();
        if ($settings) {
            config([
                'websockets.dashboard.port' => $settings->liveChatPort ?? env('LIVE_CHAT_PORT', 6001),
                'broadcasting.connections.pusher.options.port' => $settings->liveChatPort ?? env('LIVE_CHAT_PORT', 6001),
                'broadcasting.connections.pusher.options.host' => parse_url(url('/'))['host'] ?? env('PUSHER_HOST', 'localhost'),
            ]);
        }
    }

    /**
     * Get settings from database
     */
    private function getSettings(): ?Setting
    {
        try {
            return cache()->remember('notification_settings', now()->addMinutes(10), function () {
                // Use fully qualified class name to ensure correct model resolution
                $settingClass = \Modules\Core\Models\Setting::class;

                return $settingClass::query()->first();
            });
        } catch (\Exception $e) {
            // Database not ready yet, return null gracefully
            return null;
        }
    }

    /**
     * Registrar menús del módulo Notification
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Notifications
        NavService::registerMiniItem('notifications', [
            'icon' => 'fa-duotone fa-bell',
            'tooltip' => 'Notificaciones',
            'sidebar_id' => 'notifications',
            'order' => 70,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('notifications', [
            'title' => 'Notificaciones',
            'items' => [
                ['label' => 'Todas las notificaciones', 'route' => 'notifications.index'],
            ],
        ]);
    }

    /**
     * Register scheduled tasks for Notification module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Auto-delete old notifications - every minute
            $schedule->command('notification:autodelete')->everyMinute();
        });
    }
}
