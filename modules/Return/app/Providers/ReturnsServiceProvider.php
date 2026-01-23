<?php

namespace Modules\Returns\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ReturnsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Return';

    protected string $nameLower = 'returns';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Note: EventServiceProvider is intentionally NOT registered here
        // All Return events are handled by App\Providers\EventServiceProvider
        // or the specific ReturnEventServiceProvider
        $this->app->register(RouteServiceProvider::class);

        // Registrar servicios como singletons
        $this->app->singleton(
            \Modules\Returns\Services\ReturnService::class,
            fn ($app) => new \Modules\Returns\Services\ReturnService
        );

        $this->app->singleton(
            \Modules\Returns\Services\ReturnEmailService::class,
            fn ($app) => new \Modules\Returns\Services\ReturnEmailService
        );

        // Registrar ReturnObserver
        $this->registerObservers();
    }

    /**
     * Registrar observadores de modelos
     */
    protected function registerObservers(): void
    {
        if (class_exists(\Modules\Returns\Models\ReturnRequest::class)) {
            \Modules\Returns\Models\ReturnRequest::observe(
                \Modules\Returns\Observers\ReturnObserver::class
            );
        }
    }

    /**
     * Register commands in the format of Command::class
     *
     * Note: Commands are auto-discovered by Laravel 12 from:
     * - modules/Return/app/Console/Commands/
     * Manual registration is no longer needed.
     */
    protected function registerCommands(): void
    {
        // Commands are auto-discovered by Laravel 12
        // No manual registration needed
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Send return reminders daily at 10:00 AM
            $schedule->command('returns:send-reminders')
                ->dailyAt('10:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/return-reminders.log'));

            // Clean up old communications weekly on Sundays at 02:00 AM
            $schedule->command('returns:cleanup-communications --days=90')
                ->weekly()
                ->sundays()
                ->at('02:00')
                ->withoutOverlapping();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        // Only merge if the config file returned an array
        if (is_array($module_config)) {
            config([$key => array_replace_recursive($existing, $module_config)]);
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Register navigation composer for theme layout if needed
        // view()->composer(
        //     'theme.components.nav',
        //     \modules\Return\Http\ViewComposers\NavigationComposer::class
        // );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
