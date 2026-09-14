<?php

namespace Modules\PriceLabels\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PriceLabels\Console\Commands\PruneOldPriceLabelGenerationsCommand;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Policies\PriceLabelGenerationPolicy;
use Modules\PriceLabels\Policies\PriceLabelTemplatePolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class PriceLabelsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'PriceLabels';

    protected string $moduleNameLower = 'pricelabels';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerScheduledTasks();
    }

    public function register(): void {}

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

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

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));

        Route::middleware(['web', 'auth'])
            ->prefix('panel/settings/'.$this->moduleNameLower)
            ->name('settings.'.$this->moduleNameLower.'.')
            ->group(module_path($this->moduleName, 'routes/settings.php'));
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem($this->moduleNameLower, [
            'icon' => 'fas fa-tags',
            'tooltip' => 'Etiquetas de precio',
            'sidebar_id' => $this->moduleNameLower,
            'order' => 60,
        ]);

        NavService::registerSidebar($this->moduleNameLower, [
            'title' => 'Etiquetas de precio',
            'items' => [
                ['label' => 'Plantillas', 'route' => $this->moduleNameLower.'.index', 'permission' => 'pricelabels.view'],
                ['label' => 'Historial', 'route' => $this->moduleNameLower.'.history.index', 'permission' => 'pricelabels.view'],
                ['label' => 'Configuracion', 'route' => 'settings.'.$this->moduleNameLower.'.index', 'permission' => 'pricelabels.settings.view'],
            ],
        ]);

        NavService::addItemsToSection('settings', 'Etiquetas de precio', [
            [
                'label' => 'Fuentes y configuracion',
                'route' => 'settings.'.$this->moduleNameLower.'.index',
                'permission' => 'pricelabels.settings.view',
            ],
        ]);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(PriceLabelTemplate::class, PriceLabelTemplatePolicy::class);
        Gate::policy(PriceLabelGeneration::class, PriceLabelGenerationPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneOldPriceLabelGenerationsCommand::class,
            ]);
        }
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pricelabels:prune-generations')
                ->daily()
                ->name('pricelabels:prune-generations')
                ->withoutOverlapping()
                ->onOneServer();
        });
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
