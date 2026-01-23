<?php

namespace Modules\Modules\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as LaravelEventServiceProvider;
use Illuminate\Support\Facades\Blade;
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
            \Modules\Modules\Console\Commands\ModulesStatusCommand::class,
            \Modules\Modules\Console\Commands\ToggleModuleCommand::class,
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
        $configPath = module_path('Modules', config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', 'modules.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? 'modules' : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
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
        // Register menus for Modules management
        NavService::registerSidebar('settings', [
            'title' => 'Módulos',
            'items' => [
                ['label' => 'Gestión de módulos', 'route' => 'settings.modules.index'],
            ],
        ]);
    }

    public function provides(): array
    {
        return [];
    }
}
