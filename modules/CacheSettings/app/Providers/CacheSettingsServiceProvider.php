<?php

namespace Modules\CacheSettings\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class CacheSettingsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CacheSettings';

    protected string $moduleNameLower = 'cachesettings';

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
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/general.php'), 'CacheSettings.general');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/permissions.php'), 'CacheSettings.permissions');
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
