<?php

namespace Modules\Core\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Theme\Services\NavService;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';

    protected string $moduleNameLower = 'core';

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/languages.php',
            'languages'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/localization.php',
            'localization'
        );
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });

        // Register menus
        $this->registerMenus();

        // Register scheduled tasks
        $this->registerSchedules();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('panel')
            ->name('core.')
            ->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
                Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity'])->name('dashboard.activity');
                Route::get('/dashboard/queue-stats', [DashboardController::class, 'queueStats'])->name('dashboard.queue-stats');
            });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
    }

    protected function registerConfig(): void
    {
        // Register config files from module if they exist
        $configPath = module_path($this->moduleName, 'config');
        if (is_dir($configPath)) {
            foreach (glob($configPath.'/*.php') as $configFile) {
                $this->publishes([
                    $configFile => config_path(basename($configFile)),
                ], $this->moduleNameLower.'-config');
            }
        }
    }

    /**
     * Registrar menús del módulo Core
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Core/Dashboard
        NavService::registerMiniItem('dashboard', [
            'icon' => 'fa-duotone fa-home',
            'tooltip' => 'Panel de control',
            'sidebar_id' => 'dashboard',
            'url' => 'core.dashboard',
            'order' => 10,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('dashboard', [
            'title' => 'Panel de control',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'core.dashboard'],
            ],
        ]);
    }

    /**
     * Register scheduled tasks for Core module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // System cleanup - daily
            $schedule->command('system:cleanup')->daily();

            // GeoIP database check - daily
            $schedule->command('geoip:check')->daily();

            // Cache/config/route clearing is handled by the deploy pipeline, not the scheduler.
            // Running these every 30 minutes in production causes race conditions and performance issues.
        });
    }
}
