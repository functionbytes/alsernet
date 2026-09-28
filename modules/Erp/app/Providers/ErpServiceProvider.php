<?php

namespace Modules\Erp\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\LostConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Erp\Console\Commands\CacheCustomerOrders;
use Modules\Erp\Console\Commands\ClearProductImports;
use Modules\Erp\Console\Commands\ErpCheckCommand;
use Modules\Erp\Console\Commands\ExtractOracleDDL;
use Modules\Erp\Console\Commands\ImportProductsFromPrestashop;
use Modules\Erp\Console\Commands\IssueBridgeTokenCommand;
use Modules\Erp\Console\Commands\ShowImportStatistics;
use Modules\Erp\Console\Commands\Supplier\CircuitBreakerStatus;
use Modules\Erp\Console\Commands\SyncErpEndpointsCommand;
use Modules\Erp\Console\Commands\SyncProducts;
use Modules\Erp\Console\Commands\SyncSpecificPrices;
use Modules\Erp\Console\Commands\TestOracleConnection;
use Modules\Erp\Console\Commands\TestPerformance;
use Modules\Erp\Http\Middleware\ApiAuth;
use Modules\Erp\Http\Middleware\DeprecatedRoute;
use Modules\Erp\Http\Middleware\ServerTiming;
use Modules\Erp\Http\Middleware\ValidateEndpointToken;
use Modules\Erp\Services\CircuitBreaker;
use Modules\Erp\Services\ErpService;
use Modules\Erp\Services\OCI8Service;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ErpServiceProvider extends ServiceProvider
{
    private static bool $oracleResolverHardened = false;

    use PathNamespace;

    protected string $name = 'Erp';

    protected string $nameLower = 'erp';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register middleware aliases before routes so 'erp.api-auth' is resolvable
        $this->registerMiddleware();

        // Register routes with middleware wrapper (Laravel 12 compatible)
        $this->registerRoutes();

        // Register menus
        $this->registerMenus();

        // Use yajra's built-in 'dynamic' hook so Oracle settings are loaded from
        // DB/cache only when an Oracle connection is actually opened — not on
        // every HTTP request boot (avoids ~34 unnecessary cache reads per request).
        // Must use a string-based callable so the config can be cached (closures are not serializable).
        config(['database.connections.oracle.dynamic' => [self::class, 'applyDynamicOracleConfig']]);

        $this->hardenOracleResolver();

        // Register commands
        $this->registerCommands();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Cliente HTTP para las llamadas internas a /api/erp/* (Supplier...):
        // adjunta el token interno si está configurado, de modo que la API
        // pueda protegerse con ERP_API_AUTH_ENABLED sin romper esas llamadas.
        Http::macro('erpApi', function (): PendingRequest {
            $request = Http::acceptJson();
            $token = (string) config('erp.api.internal_token', '');

            return $token !== '' ? $request->withToken($token) : $request;
        });

        $this->app->singleton(ErpService::class, function ($app) {
            return new ErpService;
        });

        // Una sola instancia por petición/worker: la conexión OCI8 y su
        // marca de último uso se reutilizan entre servicios y controladores.
        $this->app->singleton(OCI8Service::class);

        // Circuit Breaker for Oracle connection resilience
        $this->app->singleton(CircuitBreaker::class, function ($app) {
            return new CircuitBreaker;
        });
    }

    /**
     * Register module middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('erp.api-auth', ApiAuth::class);
        $router->aliasMiddleware('erp.validate-endpoint-token', ValidateEndpointToken::class);
        $router->aliasMiddleware('erp.deprecated', DeprecatedRoute::class);
        $router->aliasMiddleware('erp.server-timing', ServerTiming::class);
    }

    /**
     * Limitador de /api/erp/*.
     *
     * Antes era `throttle:60,1` para todos: Supplier llama con UN solo token
     * interno y su sincronización pide /products/{id}/detailed modelo a modelo,
     * así que 6000 modelos eran ≥100 min o respuestas 429. El token interno
     * (ERP_INTERNAL_API_TOKEN, llamadas de este mismo servidor) va sin límite;
     * el resto mantiene el de config/erp.php (erp.api.throttle, "max,minutos"),
     * por usuario autenticado o, si no lo hay, por IP.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('erp-api', function (Request $request) {
            $internal = (string) config('erp.api.internal_token', '');
            $bearer = (string) $request->bearerToken();
            if ($internal !== '' && $bearer !== '' && hash_equals($internal, $bearer)) {
                return Limit::none();
            }

            [$max, $minutes] = array_map('intval', array_pad(explode(',', (string) config('erp.api.throttle', '60,1')), 2, 1));

            return Limit::perMinutes(max(1, $minutes), max(1, $max))
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });
    }

    /**
     * Register module routes with middleware wrapper
     */
    protected function registerRoutes(): void
    {
        $this->registerRateLimiters();

        $webPath = module_path($this->name, 'routes/web.php');

        // ERP Settings and Management routes (web).
        // 'can:erp.endpoints.manage' añadido — antes solo exigía auth+verified,
        // así que cualquier usuario autenticado (con cualquier rol) podía crear
        // endpoints ERP con URL arbitraria y ejecutarlos desde el servidor
        // (SSRF), generar tokens públicos para repetir esa SSRF sin sesión, y
        // sobrescribir las credenciales de Oracle en el .env vivo. El permiso
        // ya existía sembrado para admin/super-admin (ErpPermissionsSeeder)
        // pero nunca se comprobaba en ninguna ruta.
        Route::middleware(['web', 'auth', 'verified', 'can:erp.endpoints.manage'])
            ->prefix('panel/settings/erp')
            ->name('settings.erp.')
            ->group(function () use ($webPath) {
                require $webPath;
            });

        // ERP API routes (/api/erp/*)
        $apiPath = module_path($this->name, 'routes/api.php');
        if (file_exists($apiPath)) {
            Route::prefix('api')->group($apiPath);
        }
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $langModulePath = module_path($this->name, 'lang');
            if (is_dir($langModulePath)) {
                $this->loadTranslationsFrom($langModulePath, $this->nameLower);
                $this->loadJsonTranslationsFrom($langModulePath);
            }
        }
    }

    /**
     * Register config files recursively.
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
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
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
     * Get publishable view paths.
     */
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

    /**
     * Register module menus in settings.
     */
    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'ERP',
            'module' => 'Erp',
            'icon' => 'fas fa-plug',
            'items' => [
                ['label' => 'Panel General', 'route' => 'settings.erp.index'],
                ['label' => 'API del ERP', 'route' => 'settings.erp.api.edit'],
                ['label' => 'Seguridad API', 'route' => 'settings.erp.api-security.edit'],
                ['label' => 'Oracle Database', 'route' => 'settings.erp.database.index'],
                ['label' => 'Endpoints', 'route' => 'settings.erp.endpoints.index'],
            ],
        ]);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            // Oracle / ERP diagnostics
            TestOracleConnection::class,
            ErpCheckCommand::class,
            ExtractOracleDDL::class,
            TestPerformance::class,

            // Imports
            ImportProductsFromPrestashop::class,
            ClearProductImports::class,
            ShowImportStatistics::class,

            // External API / Prestashop sync
            SyncProducts::class,
            SyncSpecificPrices::class,
            CacheCustomerOrders::class,

            // Endpoint discovery
            SyncErpEndpointsCommand::class,

            // Bridge token issuance for Alsernet → ERP integration
            IssueBridgeTokenCommand::class,

            // Supplier → ERP sync pipeline (Oracle source of truth)
            CircuitBreakerStatus::class,
        ]);
    }

    /**
     * Envuelve el resolver 'oracle' de yajra para sobrevivir a una conexión
     * persistente muerta.
     *
     * yajra ejecuta ALTER SESSION (variables NLS) al CREAR la conexión, antes
     * de que DatabaseManager le asigne el reconector. Si oci_pconnect devuelve
     * un handle cortado (ORA-03113/03114, firewall), esa primera sentencia
     * lanza "Lost connection and no reconnector available" y la petición da
     * 500. Aquí se reintenta una vez con una conexión nueva no persistente.
     */
    protected function hardenOracleResolver(): void
    {
        $resolver = Connection::getResolver('oracle');
        if (! $resolver || self::$oracleResolverHardened) {
            return;
        }
        self::$oracleResolverHardened = true;

        Connection::resolverFor('oracle', function ($connection, $database, $prefix, $config) use ($resolver) {
            try {
                return $resolver($connection, $database, $prefix, $config);
            } catch (\Throwable $e) {
                if (! app(LostConnectionDetector::class)->causedByLostConnection($e)
                    && ! $e instanceof LostConnectionException) {
                    throw $e;
                }

                \Log::warning('ERP: conexión Oracle persistente caída al abrir; reintentando con una nueva', [
                    'error' => $e->getMessage(),
                ]);

                $config['options'][\PDO::ATTR_PERSISTENT] = false;
                $config['options']['cached'] = false;

                return $resolver($connection, $database, $prefix, $config);
            }
        });
    }

    /**
     * Called by yajra's 'dynamic' hook when an Oracle connection is actually opened.
     * Merges DB/cache settings into the connection config at connection time,
     * not at every HTTP request boot.
     */
    public static function applyDynamicOracleConfig(array &$config): void
    {
        try {
            $settings = Setting::getErpSettings();

            $map = [
                'oracle_host' => ['host', 'hostname'],
                'oracle_port' => ['port'],
                'oracle_database' => ['database'],
                'oracle_service_name' => ['service_name'],
                'oracle_username' => ['username'],
                'oracle_password' => ['password'],
                'oracle_schema' => ['prefix_schema'],
                'oracle_charset' => ['charset'],
            ];

            foreach ($map as $settingKey => $configKeys) {
                $value = $settings[$settingKey] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                if ($settingKey === 'oracle_port') {
                    $value = (int) $value;
                }
                foreach ($configKeys as $cfgKey) {
                    $config[$cfgKey] = $value;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('ERP: Could not load Oracle configuration', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ErpService::class,
            CircuitBreaker::class,
        ];
    }
}
