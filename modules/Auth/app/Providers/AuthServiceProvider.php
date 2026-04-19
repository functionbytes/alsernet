<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\PruneAuthLogsCommand;
use Modules\Auth\Events\LoginFailed;
use Modules\Auth\Events\NewDeviceDetected;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Http\Controllers\ImpersonationController;
use Modules\Auth\Http\Controllers\LockScreenController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\TwoFactorChallengeController;
use Modules\Auth\Http\Middleware\CheckPasswordExpired;
use Modules\Auth\Http\Middleware\CheckSessionLock;
use Modules\Auth\Http\Middleware\DenyWhenImpersonating;
use Modules\Auth\Listeners\LogLoginActivity;
use Modules\Auth\Listeners\LogLoginFailure;
use Modules\Auth\Listeners\LogTwoFactorActivity;
use Modules\Auth\Listeners\RecordPasswordChange;
use Modules\Auth\Listeners\SendNewDeviceAlert;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuthServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Event → listeners map.
     */
    protected array $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserLoggedIn::class => [
            LogLoginActivity::class,
        ],
        LoginFailed::class => [
            LogLoginFailure::class,
        ],
        PasswordChanged::class => [
            RecordPasswordChange::class,
        ],
        NewDeviceDetected::class => [
            SendNewDeviceAlert::class,
        ],
    ];

    /**
     * Event subscribers.
     */
    protected array $subscribe = [
        LogTwoFactorActivity::class,
    ];

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
        $this->registerMiddleware();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerRoutes();
    }

    public function register(): void
    {
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
     * Register module routes.
     */
    protected function registerRoutes(): void
    {
        $webPath = module_path($this->name, 'routes/web.php');
        $settingsPath = module_path($this->name, 'routes/settings.php');
        $apiPath = module_path($this->name, 'routes/api.php');

        Route::middleware(['web'])
            ->get('/', [LoginController::class, 'home'])
            ->name('auth.home');

        Route::middleware(['web', 'guest'])
            ->group(function () use ($webPath) {
                require $webPath;
            });

        // 2FA challenge + lock screen (auth required but exempt from 2FA/lock middleware)
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
            Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');

            Route::get('/lock', [LockScreenController::class, 'show'])->name('auth.lock');
            Route::post('/lock/unlock', [LockScreenController::class, 'unlock'])->name('auth.lock.unlock');
            Route::post('/lock', [LockScreenController::class, 'lock'])->name('auth.lock.lock');

            // Impersonation (start requires permission; stop does not)
            Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])
                ->name('auth.impersonation.start');
            Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
                ->name('auth.impersonation.stop');
        });

        Route::middleware(['web', 'auth', CheckSessionLock::class, CheckPasswordExpired::class])
            ->prefix('panel/setting/auth')
            ->name('settings.auth.')
            ->group(function () use ($settingsPath) {
                require $settingsPath;
            });

        Route::middleware(['api'])
            ->prefix('api/auth')
            ->name('api.auth.')
            ->group(function () use ($apiPath) {
                require $apiPath;
            });
    }

    protected function registerMenus(): void {}

    protected function registerEvents(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }
    }

    protected function registerGates(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-settings') ? true : null;
        });

        Gate::define('viewAudit', fn ($user) => $user->can('auth.audit.view'));
    }

    /**
     * Register middleware aliases used by module routes.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('auth.session.lock', CheckSessionLock::class);
        $router->aliasMiddleware('auth.deny-impersonating', DenyWhenImpersonating::class);
        $router->aliasMiddleware('auth.password.expired', CheckPasswordExpired::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneAuthLogsCommand::class,
            ]);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('auth:prune')->weekly()->sundays()->at('03:00');
        });
    }

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

    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        if (is_array($module_config)) {
            config([$key => array_replace_recursive($existing, $module_config)]);
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

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
