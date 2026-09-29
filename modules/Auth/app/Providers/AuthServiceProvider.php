<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\IpFilterCommand;
use Modules\Auth\Console\Commands\PruneAuthLogsCommand;
use Modules\Auth\Events\ImpersonationStarted;
use Modules\Auth\Events\LoginFailed;
use Modules\Auth\Events\NewDeviceDetected;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Http\Controllers\ImpersonationController;
use Modules\Auth\Http\Controllers\LockScreenController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\TwoFactorChallengeController;
use Modules\Auth\Http\Middleware\AddRateLimitHeaders;
use Modules\Auth\Http\Middleware\CheckPasswordExpired;
use Modules\Auth\Http\Middleware\CheckSessionLock;
use Modules\Auth\Http\Middleware\DenyWhenImpersonating;
use Modules\Auth\Http\Middleware\RequireTwoFactorForPrivilegedRoles;
use Modules\Auth\Http\Middleware\RestrictStaffAccessByIp;
use Modules\Auth\Listeners\LogLoginActivity;
use Modules\Auth\Listeners\LogLoginFailure;
use Modules\Auth\Listeners\LogTwoFactorActivity;
use Modules\Auth\Listeners\NotifyImpersonated;
use Modules\Auth\Listeners\RecordPasswordChange;
use Modules\Auth\Listeners\SendNewDeviceAlert;
use Modules\Auth\Services\StaffIpAllowlist;
use Modules\Theme\Services\NavService;
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
        ImpersonationStarted::class => [
            NotifyImpersonated::class,
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
        $this->registerRateLimiters();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerRoutes();
    }

    public function register(): void
    {
        $this->app->scoped(StaffIpAllowlist::class);

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

        // 2FA challenge: user is NOT authenticated yet — only session key guards access
        Route::middleware(['web'])->group(function () {
            Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
            Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');
        });

        // Lock screen + impersonation (requires auth)
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/lock', [LockScreenController::class, 'show'])->name('auth.lock');
            // throttle (29-sep-2026): sin él, una sesión robada permitía fuerza bruta de la contraseña.
            Route::post('/lock/unlock', [LockScreenController::class, 'unlock'])->name('auth.lock.unlock')->middleware('throttle:5,1');
            Route::post('/lock', [LockScreenController::class, 'lock'])->name('auth.lock.lock')->middleware('throttle:30,1');

            // stop must be before {user} to avoid wildcard capture
            Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
                ->name('auth.impersonation.stop');
            Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])
                ->name('auth.impersonation.start');
        });

        Route::middleware(['web', 'auth', CheckSessionLock::class, CheckPasswordExpired::class])
            ->prefix('panel/settings/auth')
            ->name('settings.auth.')
            ->group(function () use ($settingsPath) {
                require $settingsPath;
            });

        // Legacy redirects (singular → plural). TODO remove after 2026-12-31
        Route::redirect('panel/setting/auth/{any}', 'panel/settings/auth/{any}', 301)
            ->where('any', '.*')
            ->middleware('web');
        Route::redirect('panel/setting/auth', 'panel/settings/auth', 301)
            ->middleware('web');

        Route::middleware(['api', AddRateLimitHeaders::class])
            ->prefix('api/auth')
            ->name('api.auth.')
            ->group(function () use ($apiPath) {
                require $apiPath;
            });
    }

    /**
     * Entradas del módulo en el menú lateral de configuración. Cada una usa el
     * mismo permiso que su controlador, así que solo la ve quien puede abrirla.
     */
    protected function registerMenus(): void
    {
        NavService::addItemsToSection('settings', 'Seguridad y acceso', [
            ['label' => 'Configuración de seguridad', 'route' => 'settings.security.config', 'permission' => 'security.config.manage'],
            ['label' => 'Acceso al panel por IP', 'route' => 'settings.auth.ip-filter', 'permission' => 'auth.ip-filter.manage'],
            ['label' => 'Intentos de login', 'route' => 'settings.auth.audit.login-attempts', 'permission' => 'auth.audit.view'],
            ['label' => 'Suplantaciones', 'route' => 'settings.auth.audit.impersonations', 'permission' => 'auth.audit.view'],
        ]);
    }

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
        /*
         * Aquí había un Gate::before que devolvía true para CUALQUIER permiso
         * si el usuario tenía el rol `super-settings`. Con 1.141 usuarios en
         * ese rol, los permisos y las Policies de los 40 módulos no decidían
         * nada para la mayor parte del panel: se podía retirar un permiso desde
         * la interfaz de roles y el acceso seguía abierto.
         *
         * Retirado el 7-sep-2026. El rol tenía 61 de 447 permisos asignados,
         * así que antes de quitarlo se ejecutó
         * SuperSettingsExplicitPermissionsSeeder, que le da los 447: el acceso
         * del primer día es idéntico, pero ahora es el permiso quien manda y
         * quitar uno surte efecto de verdad.
         *
         * Vuelve a ejecutar ese seeder cuando instales un módulo nuevo, o sus
         * permisos recién sembrados no llegarán al rol.
         *
         * Un cambio de comportamiento que conviene conocer: las Policies con
         * lógica propia (del tipo "solo el autor edita su nota") ahora también
         * se aplican a este rol, que antes las saltaba.
         */
        Gate::define('viewAudit', fn ($user) => $user->can('auth.audit.view'));
        // Solo super-admin gestiona las redes permitidas (mismo criterio que
        // StaffIpFilterController); se usa también para mostrar el menú.
        Gate::define('auth.ip-filter.manage', fn ($user) => $user->hasRole('super-admin'));
    }

    /**
     * Named limiter para /forgot-password (throttle:password-reset en
     * routes/web.php). Encontrado sin registrar por un barrido de
     * route:list — sin él, ThrottleRequests trata el nombre como
     * maxAttempts numérico crudo en vez de aplicar un límite real.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));
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
        $router->aliasMiddleware('auth.require-2fa', RequireTwoFactorForPrivilegedRoles::class);
        $router->aliasMiddleware('auth.staff-ip', RestrictStaffAccessByIp::class);

        // Filtro por IP del personal (29-sep-2026): se añade al final de los grupos
        // `web` y `api` (tras StartSession) y el propio middleware solo actúa en las
        // rutas de auth-policy.staff_ip_filter.web_paths/api_paths (login, 2FA,
        // recuperación, /panel/*, /impersonate/*, /broadcasting/auth, /api/auth/*).
        // Portal, widget, webhooks, /api/documents, /api/erp, /app y /up no se tocan.
        // Se añade en el Kernel HTTP (no solo en el router): el Kernel re-sincroniza
        // sus grupos con el router al resolverse y borraría un push hecho solo ahí.
        $this->callAfterResolving(HttpKernel::class, function ($kernel) {
            foreach (['web', 'api'] as $group) {
                try {
                    $kernel->appendMiddlewareToGroup($group, RestrictStaffAccessByIp::class);
                } catch (\InvalidArgumentException) {
                    // Grupo no definido (p. ej. consola sin Kernel HTTP configurado).
                }
            }

            // Antes de `auth`: si no, la prioridad de middleware ejecuta Authenticate
            // primero y una petición sin sesión de fuera se redirige sin registrarse.
            $kernel->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, RestrictStaffAccessByIp::class);
        });

        // EnforceImpersonationTimeout, CheckPasswordExpired y RequireTwoFactorForPrivilegedRoles
        // van además en el grupo `web` (bootstrap/app.php) para cubrir todo el panel.
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneAuthLogsCommand::class,
                IpFilterCommand::class,
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
