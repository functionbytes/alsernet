<?php

namespace Modules\Analytics\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Abstracts\AnalyticsAbstract;
use Modules\Analytics\Analytics;
use Modules\Analytics\Exceptions\InvalidConfiguration;
use Modules\Analytics\Facades\Analytics as AnalyticsFacade;
use Modules\Theme\Services\NavService;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AnalyticsAbstract::class, function () {
            if (! ($credentials = setting('analytics_service_account_credentials'))) {
                throw InvalidConfiguration::credentialsIsNotValid();
            }

            if (! ($propertyId = setting('analytics_property_id')) || ! is_numeric($propertyId)) {
                throw InvalidConfiguration::invalidPropertyId();
            }

            return new Analytics($propertyId, $credentials);
        });

        AliasLoader::getInstance()->alias('Analytics', AnalyticsFacade::class);
    }

    /**
     * Boot services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'analytics');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/analytics.php' => config_path('analytics.php'),
        ], 'analytics-config');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/analytics'),
        ], 'analytics-views');

        $this->registerMenus();
    }

    /**
     * Registrar menus del modulo Analytics
     */
    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Analytics',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'analytics.dashboard'],
                ['label' => 'Configuracion', 'route' => 'settings.analytics.index'],
            ],
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AnalyticsAbstract::class,
        ];
    }
}
