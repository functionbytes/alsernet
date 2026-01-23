<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Http\Controllers\LoginController;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuthServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Event listeners for the Auth module
     */
    protected array $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

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
        $this->registerMenus();
        $this->registerEvents();
        $this->registerGates();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register routes directly (Laravel 12 compatible)
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge module configs
        $this->mergeConfigFrom(
            __DIR__.'/../../config/verification.php',
            'verification'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/sanctum.php',
            'sanctum'
        );
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        $webPath = module_path($this->name, 'routes/web.php');
        $settingsPath = module_path($this->name, 'routes/settings.php');
        $apiPath = module_path($this->name, 'routes/api.php');

        // Root route - handles both authenticated and guest users
        Route::middleware(['web'])
            ->get('/', [LoginController::class, 'home'])
            ->name('auth.home');

        // Public authentication routes (login, register, password reset)
        Route::middleware(['web', 'guest'])
            ->group(function () use ($webPath) {
                require $webPath;
            });

        // Auth settings routes (2FA, sessions, security)
        Route::middleware(['web', 'auth'])
            ->prefix('settings/auth')
            ->name('settings.auth.')
            ->group(function () use ($settingsPath) {
                require $settingsPath;
            });

        // API routes
        Route::middleware(['api'])
            ->prefix('api/auth')
            ->name('api.auth.')
            ->group(function () use ($apiPath) {
                require $apiPath;
            });
    }

    /**
     * Register menus del módulo Auth
     */
    protected function registerMenus(): void {}

    /**
     * Register event listeners for the Auth module
     */
    protected function registerEvents(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }
    }

    /**
     * Register authorization gates for the Auth module
     */
    protected function registerGates(): void
    {
        // Super admin gate - grants all permissions to users with super-admin role
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
