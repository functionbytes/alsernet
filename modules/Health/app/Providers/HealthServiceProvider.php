<?php

namespace Modules\Health\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Health\Checks\DatabaseCheck;
use Modules\Health\Checks\RedisCheck;
use Modules\Health\Checks\StorageCheck;
use Modules\Theme\Services\NavService;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/health.php',
            'health'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'health');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/health.php' => config_path('health.php'),
        ], 'health-config');

        // Publish vendor views
        $this->publishes([
            __DIR__.'/../../resources/views/vendor/health' => resource_path('views/vendor/health'),
        ], 'health-views');

        // Register Spatie Health Checks
        $this->registerHealthChecks();

        // Register menus
        $this->registerMenus();
    }

    protected function registerHealthChecks()
    {
        // Detectar si realmente estamos en producción (no .test, .local, localhost)
        $isRealProduction = app()->environment('production')
            && ! str_contains(config('app.url'), '.test')
            && ! str_contains(config('app.url'), '.local')
            && ! str_contains(config('app.url'), 'localhost');

        $checks = [
            // Environment & Configuration
            EnvironmentCheck::new()->expectEnvironment(app()->environment()),

            // Database
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),

            // Cache & Redis
            CacheCheck::new(),
            RedisCheck::new(),

            // Storage
            StorageCheck::new(),

            // Monitoreo de espacio en disco (umbrales altos)
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(97)
                ->failWhenUsedSpaceIsAbovePercentage(99),
        ];

        Health::checks($checks);
    }

    /**
     * Registrar menús del módulo HealthCheck
     */
    protected function registerMenus(): void
    {

        // Sidebar con los items del módulo
        NavService::registerSidebar('settings', [
            'title' => 'Estado del sistema',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'settings.health.index'],
                ['label' => 'Historial', 'route' => 'settings.health.history'],
            ],
        ]);
    }
}
