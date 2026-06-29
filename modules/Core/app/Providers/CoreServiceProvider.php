<?php

namespace Modules\Core\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\Commands\OptimizeProductionCommand;
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

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                OptimizeProductionCommand::class,
            ]);
        }
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
                Route::get('/dashboard/trends', [DashboardController::class, 'trends'])->name('dashboard.trends');
                Route::get('/dashboard/health', [DashboardController::class, 'health'])->name('dashboard.health');
                Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
                Route::get('/dashboard/distribution', [DashboardController::class, 'distribution'])->name('dashboard.distribution');
                Route::get('/dashboard/latest-reviews', [DashboardController::class, 'latestReviews'])->name('dashboard.latest-reviews');
                Route::get('/dashboard/security-metrics', [DashboardController::class, 'securityMetrics'])->name('dashboard.security-metrics');
                Route::get('/dashboard/login-timeline', [DashboardController::class, 'loginAttemptsTimeline'])->name('dashboard.login-timeline');
                Route::get('/dashboard/top-failed-ips', [DashboardController::class, 'topFailedIps'])->name('dashboard.top-failed-ips');
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
            'icon' => 'fa-duotone fa-thin fa-gauge-high',
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
