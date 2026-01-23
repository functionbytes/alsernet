<?php

namespace Modules\Analytics\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Analytics;
use Modules\Theme\Services\NavService;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Analytics singleton
        $this->app->singleton('analytics', function ($app) {
            $propertyId = setting('google_analytics_property_id', '');
            $credentials = setting('google_analytics_credentials', '');

            return new Analytics($propertyId, $credentials);
        });
    }

    /**
     * Boot services.
     */
    public function boot(): void
    {
        // ✅ CRITICAL: Only boot module features if Analytics module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('Analytics')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'analytics');

        // Register facade alias - commented out as module is disabled
        // Facade::aliasNamespace('Analytics', 'Modules\\Analytics\\Facades');

        // Register menus
        $this->registerMenus();
    }

    /**
     * Registrar menús del módulo Analytics
     */
    protected function registerMenus(): void
    {

        // Sidebar con los items del módulo
        NavService::registerSidebar('settings', [
            'title' => 'Analítica',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'analytics.dashboard'],
                ['label' => 'Configuración', 'route' => 'settings.analytics.index'],
            ],
        ]);
    }
}
