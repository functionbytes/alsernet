<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Notification\Console\Commands\CleanupNotificationsCommand;
use Modules\Notification\Console\Commands\SendNotificationDigestCommand;
use Modules\Notification\Observers\DatabaseNotificationObserver;
use Modules\Notification\Policies\NotificationSettingsPolicy;
use Modules\Notification\Services\NotificationTypeRegistry;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

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

        $this->app->singleton(NotificationTypeRegistry::class);

        // Register commands
        $this->registerCommands();
    }

    public function boot(): void
    {
        if (Module::find('Notification')?->isDisabled()) {
            return;
        }

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

        // Observe database notifications to broadcast real-time events
        DatabaseNotification::observe(DatabaseNotificationObserver::class);

        // Configure WebSocket/Pusher settings (deferred to avoid boot issues)
        $this->app->booted(function () {
            $this->configureWebSocketSettings();
        });

        // Register authorization policies (Gates)
        $this->registerPolicies();

        // Load persisted notification settings from DB into config
        $this->app->booted(function () {
            $this->loadNotificationSettings();
        });

        // Register notification types
        $this->registerNotificationTypes();

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
            ->prefix('panel/notifications')
            ->name('notifications.')
            ->group(function () use ($modulePath) {
                require $modulePath.'/routes/web.php';
            });

        // Notification backups routes
        Route::middleware(['web', 'auth:web', 'settings'])
            ->prefix('panel/setting/notifications')
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

    /**
     * Register authorization policies for the Notification module.
     */
    protected function registerPolicies(): void
    {
        $policy = new NotificationSettingsPolicy;

        Gate::define('notification.settings.view', fn ($user) => $policy->viewAny($user));
        Gate::define('notification.settings.update', fn ($user) => $policy->update($user));
    }

    protected function registerCommands(): void
    {
        $this->commands([
            CleanupNotificationsCommand::class,
            SendNotificationDigestCommand::class,
        ]);
    }

    /**
     * Load persisted notification settings from DB into the config system
     * so that config('notification.*') reflects saved values.
     */
    protected function loadNotificationSettings(): void
    {
        $keys = [
            'notification.cleanup.enabled',
            'notification.cleanup.days',
            'notification.push_notifications.enabled',
            'notification.push_notifications.max_retries',
            'notification.channels.database',
            'notification.channels.mail',
            'notification.channels.push',
            'notification.retention_days',
        ];

        try {
            $settings = Setting::query()
                ->whereIn('key', $keys)
                ->pluck('value', 'key');

            foreach ($settings as $key => $value) {
                if ($value !== null) {
                    config([$key => $value]);
                }
            }
        } catch (\Exception) {
            // Database not ready — silently skip
        }
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
                $settingClass = Setting::class;

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
            'icon' => 'fas fa-bell',
            'tooltip' => 'Notificaciones',
            'sidebar_id' => 'notifications',
            'url' => 'notifications.index',
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
     * Register all known notification types in the registry
     */
    protected function registerNotificationTypes(): void
    {
        $this->app->booted(function () {
            $registry = $this->app->make(NotificationTypeRegistry::class);

            $registry->register('reviews.new_review', 'Nueva reseña', 'Cuando se recibe cualquier reseña nueva', ['mail', 'database']);
            $registry->register('reviews.negative_review', 'Reseña negativa', 'Reseñas con calificación baja (1-3 estrellas)', ['mail', 'database']);
            $registry->register('reviews.positive_review', 'Reseña positiva', 'Reseñas con calificación alta (4-5 estrellas)', ['database']);
            $registry->register('reviews.export_ready', 'Exportación lista', 'Cuando una exportación de reseñas está disponible', ['database']);
            $registry->register('reviews.connection_expiring', 'Conexión expirando', 'Cuando una conexión de Google está por expirar', ['mail', 'database']);
            $registry->register('reviews.daily_digest', 'Resumen diario de reseñas', 'Resumen diario de actividad de reseñas', ['mail']);
            $registry->register('attention.status_changed', 'Estado de PQRSF actualizado', 'Cuando cambia el estado de una solicitud de atención', ['database']);
            $registry->register('backup.success', 'Backup exitoso', 'Cuando un backup del sistema se completa correctamente', ['mail']);
            $registry->register('backup.failed', 'Backup fallido', 'Cuando un backup del sistema falla', ['mail']);
            $registry->register('alerts.threshold_triggered', 'Alerta por umbral', 'Cuando se dispara una alerta del sistema', ['database']);
            $registry->register('blog.new_comment', 'Nuevo comentario en blog', 'Cuando alguien comenta en un post', ['mail', 'database']);
            $registry->register('forms.new_submission', 'Nueva respuesta de formulario', 'Cuando se envía un formulario del sitio', ['mail', 'database']);
            $registry->register('page.published', 'Página publicada', 'Cuando una página es publicada en el sistema', ['mail', 'database']);
        });
    }

    /**
     * Register scheduled tasks for Notification module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Auto-delete old notifications - respects NOTIFICATION_CLEANUP_ENABLED and NOTIFICATION_CLEANUP_DAYS
            if (config('notification.cleanup.enabled', true)) {
                $schedule->command('notifications:cleanup', [
                    '--days' => config('notification.cleanup.days', 30),
                ])->dailyAt('00:00')->withoutOverlapping();
            }

            // Daily notification digest - dispatches per-user jobs at 08:00
            $schedule->command('notifications:send-digest')->dailyAt('08:00')->withoutOverlapping();
        });
    }
}
