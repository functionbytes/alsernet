<?php

namespace Modules\System\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\System\Services\SystemInfoService;
use Modules\Theme\Services\NavService;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register SystemInfoService as singleton
        $this->app->singleton(SystemInfoService::class, function ($app) {
            return new SystemInfoService;
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/system.php' => config_path('system.php'),
        ], 'system-config');

        // Register menus
        $this->registerMenus();
    }

    /**
     * Registrar menús del módulo System
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Settings (configuraciones)
        NavService::registerMiniItem('settings', [
            'icon' => 'fa-duotone fa-sliders',
            'tooltip' => 'Configuraciones',
            'sidebar_id' => 'settings',
            'order' => 100,
        ]);

        // Agregar configuraciones de sistema al sidebar genérico 'settings'
        NavService::registerSidebar('settings', [
            'title' => 'Configuraciones',
            'items' => [
                ['label' => 'Configuración', 'route' => 'settings.system.index'],
                ['label' => 'Información del sistema', 'route' => 'settings.system.info.index'],
                ['label' => 'Supervisor', 'route' => 'settings.system.supervisor.index'],
                ['label' => 'Logs del servidor', 'route' => 'settings.system.access.index'],
                ['label' => 'Carga de archivos', 'route' => 'settings.system.uploading.index'],
                ['label' => 'Cache y mantenimiento', 'route' => 'settings.system.maintenance.index'],
            ],
        ]);
    }
}
