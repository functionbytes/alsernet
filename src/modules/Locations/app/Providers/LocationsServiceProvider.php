<?php

namespace Modules\Locations\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;
use Modules\Locations\Policies\CityPolicy;
use Modules\Locations\Policies\CountryPolicy;
use Modules\Locations\Policies\StatePolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class LocationsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Locations';

    protected string $moduleNameLower = 'locations';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerPolicies();
    }

    public function register(): void {}

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->moduleNameLower
        );
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));

        Route::middleware('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem($this->moduleNameLower, [
            'icon' => 'fa-duotone fa-thin fa-location-dot',
            'tooltip' => 'Ubicaciones',
            'sidebar_id' => $this->moduleNameLower,
            'order' => 70,
        ]);

        NavService::registerSidebar($this->moduleNameLower, [
            'title' => 'Ubicaciones',
            'items' => [
                ['label' => 'Países', 'route' => 'locations.countries.index', 'permission' => 'locations.countries.view'],
                ['label' => 'Estados', 'route' => 'locations.states.index', 'permission' => 'locations.states.view'],
                ['label' => 'Ciudades', 'route' => 'locations.cities.index', 'permission' => 'locations.cities.view'],
                ['label' => 'Importar / Exportar', 'route' => 'locations.import', 'permission' => 'locations.countries.create'],
            ],
        ]);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(State::class, StatePolicy::class);
        Gate::policy(City::class, CityPolicy::class);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
