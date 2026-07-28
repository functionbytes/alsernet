<?php

namespace Modules\GiftMessage\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\GiftMessage\Console\Commands\PruneOldGiftMessageGenerationsCommand;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageGeneration;
use Modules\GiftMessage\Policies\GiftMessageConfigPolicy;
use Modules\GiftMessage\Policies\GiftMessageGenerationPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class GiftMessageServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'GiftMessage';

    protected string $moduleNameLower = 'giftmessage';

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
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem($this->moduleNameLower, [
            'icon' => 'fas fa-gift',
            'tooltip' => 'Mensaje regalo',
            'sidebar_id' => $this->moduleNameLower,
            'order' => 61,
        ]);

        NavService::registerSidebar($this->moduleNameLower, [
            'title' => 'Mensaje regalo',
            'items' => [
                ['label' => 'Panel', 'route' => $this->moduleNameLower.'.index', 'permission' => 'giftmessage.view'],
                ['label' => 'Historial', 'route' => $this->moduleNameLower.'.history.index', 'permission' => 'giftmessage.view'],
            ],
        ]);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(GiftMessageConfig::class, GiftMessageConfigPolicy::class);
        Gate::policy(GiftMessageGeneration::class, GiftMessageGenerationPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneOldGiftMessageGenerationsCommand::class,
            ]);
        }
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('giftmessage:prune-generations')
                ->daily()
                ->name('giftmessage:prune-generations')
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
