<?php

namespace Modules\Modules\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as LaravelEventServiceProvider;
use Illuminate\Support\Facades\Blade;
use Modules\Modules\Console\Commands\ModulesStatusCommand;
use Modules\Modules\Console\Commands\ToggleModuleCommand;
use Modules\Theme\Services\NavService;

class EventServiceProvider extends LaravelEventServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Modules', 'database/migrations'));
        $this->registerMenus();
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}

    protected function registerCommands(): void
    {
        $this->commands([
            ModulesStatusCommand::class,
            ToggleModuleCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/modules');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'modules');
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path('Modules', 'lang'), 'modules');
            $this->loadJsonTranslationsFrom(module_path('Modules', 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configFile = module_path('Modules', 'config/config.php');

        $this->publishes([
            $configFile => config_path('modules.php'),
        ], 'config');

        $this->mergeConfigFrom($configFile, 'modules');
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/modules');
        $sourcePath = module_path('Modules', 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', 'modules-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'modules');

        Blade::componentNamespace(config('modules.namespace').'\\Modules\\View\\Components', 'modules');
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/modules')) {
                $paths[] = $path.'/modules/modules';
            }
        }

        return $paths;
    }

    protected function registerMenus(): void
    {

        NavService::addSidebarItems('settings', [
            ['label' => 'Gestión de módulos', 'route' => 'settings.modules.index'],
        ]);

    }

    public function provides(): array
    {
        return [];
    }
}
