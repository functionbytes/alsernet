<?php

namespace Modules\Backup\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/backup.php',
            'backup'
        );

        // Register backup settings singleton
        $this->app->singleton('app.backups', fn () => $this->getBackupSettings());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'backup');

        $this->publishes([
            __DIR__.'/../../config/backup.php' => config_path('backup.php'),
        ], 'backup-config');

        $this->registerMenus();
        $this->registerBackupEventHandlers();
        $this->registerSchedules();
    }

    /**
     * Get backup settings from database
     */
    private function getBackupSettings(): ?Setting
    {
        try {
            return cache()->remember('backup_settings', now()->addMinutes(10), fn () => Setting::first());
        } catch (\Exception $e) {
            // Database not ready yet, return null gracefully
            return null;
        }
    }

    /**
     * Registrar menús del módulo Backup
     */
    protected function registerMenus(): void
    {

        NavService::registerSidebar('settings', [
            'title' => 'Copias de seguridad',
            'items' => [
                ['label' => 'Todas las copias', 'route' => 'settings.backups.index'],
                ['label' => 'Programación', 'route' => 'settings.backup.schedules.index'],
            ],
        ]);
    }

    /**
     * Register handlers for backup events to suppress notification errors
     * and handle backup-specific event logic
     */
    protected function registerBackupEventHandlers(): void
    {
        // Event handlers for backup module can be registered here
        // This allows the Backup module to manage its own event listeners
        // without cluttering the global EventServiceProvider
    }

    /**
     * Register scheduled tasks for Backup module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Run backup - daily at 3 AM
            $schedule->command('backup:run')
                ->dailyAt('03:00')
                ->withoutOverlapping(120)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/backup.log'));

            // Clean old backups - daily at 4 AM
            $schedule->command('backup:clean')
                ->dailyAt('04:00')
                ->withoutOverlapping(60)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/backup-clean.log'));

            // Monitor backup health - daily at 5 AM
            $schedule->command('backup:monitor')
                ->dailyAt('05:00')
                ->withoutOverlapping(60)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/backup-monitor.log'));

            // Run scheduled backups - every minute
            $schedule->command('app:run-scheduled-backups')
                ->everyMinute()
                ->withoutOverlapping(2)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduled-backups.log'));
        });
    }
}
