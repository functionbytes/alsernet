<?php

namespace Modules\Cache\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class CacheServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Cache';

    protected string $moduleNameLower = 'Cache';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMenus();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/general.php'), 'Cache.general');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/permissions.php'), 'Cache.permissions');
    }

    public function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');
        $this->loadViewsFrom([$sourcePath], $this->moduleNameLower);
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Cache',
            'items' => [
                ['label' => 'Configuracion de cache', 'route' => 'settings.cache.index'],
            ],
        ]);
    }

    protected function registerRoutes(): void
    {
        // Routes registered in RouteServiceProvider
    }
}
