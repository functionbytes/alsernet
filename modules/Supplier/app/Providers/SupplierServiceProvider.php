<?php

namespace Modules\Supplier\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Commands\CleanupSyncCacheCommand;
use Modules\Supplier\Console\Commands\BackfillPendingContentCommand;
use Modules\Supplier\Console\Commands\DetectDeadSyncBatchesCommand;
use Modules\Supplier\Console\Commands\GenerateContentCommand;
use Modules\Supplier\Console\Commands\RetryTransientFailuresCommand;
use Modules\Supplier\Console\Commands\RunModelSyncCommand;
use Modules\Supplier\Console\Commands\RunProductSyncCommand;
// use Modules\Supplier\Events\SupplierErpProviderUpdated;
// use Modules\Supplier\Events\SupplierProductPriceChanged;
use Modules\Supplier\Console\Commands\ShowCategoryTreeCommand;
// use Modules\Supplier\Listeners\SyncPriceToErpListener;
use Modules\Supplier\Console\Commands\TestRegisterOnlySync;
// use Modules\Supplier\Listeners\SyncProviderToErpListener;
// use Modules\Supplier\Models\SupplierErpProvider;
use Modules\Supplier\Events\SupplierProductUpdated;
// use Modules\Supplier\Models\SupplierProductPrice;
// use Modules\Supplier\Observers\SupplierErpProviderObserver;
use Modules\Supplier\Listeners\SyncProductToErpListener;
// use Modules\Supplier\Observers\SupplierProductPriceObserver;
use Modules\Supplier\Models\Ai\AiBudget;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Models\Sync\SyncSetting;
use Modules\Supplier\Observers\AiBudgetObserver;
use Modules\Supplier\Observers\AiContentVersionObserver;
use Modules\Supplier\Observers\CategoryObserver;
use Modules\Supplier\Observers\SportObserver;
use Modules\Supplier\Observers\SupplierProductObserver;
use Modules\Supplier\Observers\SupplierProductPriceObserver;
use Modules\Supplier\Observers\SyncSettingObserver;
use Modules\Supplier\Policies\SupplierPolicy;
use Modules\Supplier\Services\Ai\AiApiClient;
use Modules\Supplier\Services\AutomationOrchestrationService;
use Modules\Supplier\Services\ContentGenerationService;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\ExtractionService;
use Modules\Supplier\Services\PromptSelectionService;
use Modules\Supplier\Services\SourceConfigurationService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SupplierServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Supplier';

    protected string $nameLower = 'supplier';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerGates();
        $this->registerRateLimiters();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->registerMenus();
        $this->registerObservers();
        $this->registerEventListeners();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerPolicies();

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/supplier.php',
            'supplier'
        );

        // Register services as singletons
        $this->app->singleton(
            PromptSelectionService::class,
            fn ($app) => new PromptSelectionService
        );

        $this->app->singleton(
            ExtractionService::class,
            fn ($app) => new ExtractionService
        );

        $this->app->singleton(
            AutomationOrchestrationService::class,
            fn ($app) => new AutomationOrchestrationService
        );

        $this->app->singleton(
            ContentGenerationService::class,
            fn ($app) => new ContentGenerationService(
                $app->make(AiApiClient::class),
                $app->make(PromptSelectionService::class),
            )
        );

        $this->app->singleton(
            SourceConfigurationService::class,
            fn ($app) => new SourceConfigurationService
        );

        // Registrar servicio de sincronización inversa (Supplier → ERP)
        $this->app->singleton(
            ErpSyncService::class,
            fn ($app) => new ErpSyncService
        );
    }

    protected function registerPolicies(): void
    {
        if (class_exists(Supplier::class)) {
            Gate::policy(Supplier::class, SupplierPolicy::class);
        }
    }

    protected function registerGates(): void
    {
        $supplierAbilities = [
            'suppliers.configure',
            'suppliers.import',
            'suppliers.view',
            'suppliers.sync.erp',
            'suppliers.sync.trigger',
            'suppliers.sync.config',
            'suppliers.sync.status',
            'suppliers.sync.failures',
            'suppliers.automation.manage',
            'suppliers.content.manage',
            'suppliers.route.operational',
            'suppliers.route.settings',
        ];

        Gate::before(function ($user, $ability) use ($supplierAbilities) {
            if (str_starts_with($ability, 'suppliers.') || in_array($ability, $supplierAbilities)) {
                if ($user->hasRole('super-admin')) {
                    return true;
                }
            }

            return null;
        });

        Gate::define('suppliers.configure', fn ($user) => $user->hasRole('super-admin') || $user->hasPermissionTo('suppliers.configure'));
        Gate::define('suppliers.import', fn ($user) => $user->hasRole('super-admin') || $user->hasPermissionTo('suppliers.import'));
        Gate::define('can_sync_suppliers', fn ($user) => $user->hasRole('super-admin') || $user->hasPermissionTo('suppliers.sync.status'));

        // Definición explícita de todos los permisos usados en rutas y controladores.
        // Garantiza que Gate resuelva correctamente en entornos sin seeder ejecutado.
        $routeAbilities = [
            'suppliers.view',
            'suppliers.view.detail',
            'suppliers.view.products',
            'suppliers.view.content',
            'suppliers.view.automation',
            'suppliers.sync.config',
            'suppliers.sync.status',
            'suppliers.sync.failures',
            'suppliers.sync.erp',
            'suppliers.sync.trigger',
            'suppliers.sources.manage',
            'suppliers.categories.manage',
            'suppliers.prompts.manage',
            'suppliers.templates.manage',
            'suppliers.monitoring.view',
            'suppliers.monitoring.manage',
            'suppliers.automation.manage',
            'suppliers.content.manage',
            'suppliers.content.assign-others',
            'suppliers.route.operational',
            'suppliers.route.settings',
        ];

        foreach ($routeAbilities as $ability) {
            Gate::define($ability, fn ($user) => $user->hasRole('super-admin') || $user->hasPermissionTo($ability));
        }
    }

    /**
     * Register named rate limiters used by Supplier routes.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('ai-chat', function ($request) {
            return [
                Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(200)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Bulk AI generation is much more expensive per request — limit harder.
        RateLimiter::for('ai-bulk', function ($request) {
            return [
                Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CleanupSyncCacheCommand::class,
            ShowCategoryTreeCommand::class,
            RunModelSyncCommand::class,
            RunProductSyncCommand::class,
            DetectDeadSyncBatchesCommand::class,
            RetryTransientFailuresCommand::class,
            GenerateContentCommand::class,
            BackfillPendingContentCommand::class,
            TestRegisterOnlySync::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // The scheduler only runs in CLI (artisan schedule:run). In HTTP
        // requests this code is dead weight that, worse, executes a SELECT
        // against supplier_sync_schedules on every request via
        // scheduleSyncCommands() below. Skip the whole boot wiring outside CLI.
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Cleanup stale sync cache flags every hour
            $schedule->command('supplier:cleanup-sync-cache')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();

            // Detect sync batches stuck in running state every 10 minutes
            $schedule->command('supplier:sync:detect-dead-batches --fix')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            // Retry transient sync failures every 30 minutes
            $schedule->command('supplier:retry-transient-failures')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            try {
                if (! Schema::hasTable('supplier_sync_schedules')) {
                    return;
                }
            } catch (\Throwable) {
                return;
            }

            $this->scheduleSyncCommands($schedule, 'model', 'supplier:sync-models');
            $this->scheduleSyncCommands($schedule, 'product', 'supplier:sync-products');
        });
    }

    /**
     * Register dynamic sync schedules from the supplier_sync_schedules table.
     */
    protected function scheduleSyncCommands(Schedule $schedule, string $syncType, string $command): void
    {
        try {
            $entries = SyncSchedule::query()
                ->where('sync_type', $syncType)
                ->where('is_enabled', true)
                ->get();

            foreach ($entries as $entry) {
                $schedule->command($command.' --schedule-uid='.$entry->uid)
                    ->dailyAt(sprintf('%02d:%02d', $entry->hour, $entry->minute))
                    ->withoutOverlapping()
                    ->runInBackground();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to register dynamic sync schedules', [
                'sync_type' => $syncType,
                'error' => $e->getMessage(),
            ]);
        }
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
     * Register view composers for theme integration.
     */
    protected function registerViewComposers(): void
    {
        // Compositors específicos del módulo Supplier pueden ser agregados aquí si es necesario
    }

    /**
     * Register navigation menus
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Proveedores
        NavService::registerMiniItem('suppliers', [
            'icon' => 'fa-duotone fa-thin fa-truck-field',
            'tooltip' => 'Proveedores',
            'sidebar_id' => 'suppliers',
            'order' => 50,
            'module' => 'Supplier',
        ]);

        // Sidebar operational - Gestión de suppliers (sidebar lateral)
        // Organizado en secciones: Catálogo, Sincronización ERP, Automatización, IA & Prompts
        NavService::registerSidebar('suppliers', [
            'title' => 'Catálogo',
            'module' => 'Supplier',
            'items' => [
                ['label' => 'Proveedores', 'route' => 'settings.suppliers.index', 'permission' => 'suppliers.view'],
                ['label' => 'Productos', 'route' => 'settings.suppliers.products.index', 'permission' => 'suppliers.view.products'],
                ['label' => 'Categorías', 'route' => 'settings.suppliers.categories.index', 'permission' => 'suppliers.configure'],
                ['label' => 'Características', 'route' => 'settings.suppliers.characteristics.index', 'permission' => 'suppliers.configure'],
                ['label' => 'Contenido generado', 'route' => 'settings.suppliers.content.index', 'permission' => 'suppliers.view.content'],
            ],
        ]);

        NavService::registerSidebar('suppliers', [
            'title' => 'Sincronización ERP',
            'module' => 'Supplier',
            'items' => [
                ['label' => 'Importar desde ERP', 'route' => 'settings.suppliers.import.index', 'permission' => 'suppliers.import'],
                ['label' => 'Estado de sync', 'route' => 'settings.suppliers.sync.index', 'permission' => 'suppliers.sync.status'],
                ['label' => 'Fallos de sync', 'route' => 'settings.suppliers.sync.failures.index', 'permission' => 'suppliers.sync.failures'],
            ],
        ]);

        NavService::registerSidebar('suppliers', [
            'title' => 'Automatización',
            'module' => 'Supplier',
            'items' => [
                ['label' => 'Workflows', 'route' => 'settings.suppliers.automation.index', 'permission' => 'suppliers.view.automation'],
                ['label' => 'Logs', 'route' => 'settings.suppliers.automation.logs', 'permission' => 'suppliers.view.automation'],
                ['label' => 'Detector de fuentes', 'route' => 'settings.suppliers.detect.show', 'permission' => 'suppliers.configure'],
            ],
        ]);

        NavService::registerSidebar('suppliers', [
            'title' => 'IA & Prompts',
            'module' => 'Supplier',
            'items' => [
                ['label' => 'Prompts', 'route' => 'settings.suppliers.prompts.index', 'permission' => 'suppliers.prompts.manage'],
                ['label' => 'Templates', 'route' => 'settings.suppliers.templates.index', 'permission' => 'suppliers.templates.manage'],
                ['label' => 'Monitoreo IA', 'route' => 'settings.suppliers.monitoring.index', 'permission' => 'suppliers.monitoring.view'],
                ['label' => 'Logs IA', 'route' => 'settings.suppliers.monitoring.logs', 'permission' => 'suppliers.monitoring.view'],
            ],
        ]);

        NavService::registerSidebar('suppliers', [
            'title' => 'Configuración',
            'module' => 'Supplier',
            'items' => [
                ['label' => 'Configuración sync', 'route' => 'settings.suppliers.sync.config.http', 'permission' => 'suppliers.sync.config'],
                ['label' => 'Endpoints ERP', 'route' => 'settings.suppliers.endpoints.index', 'permission' => 'suppliers.sync.config'],
                ['label' => 'Configuración IA', 'route' => 'settings.suppliers.ai-config.index', 'permission' => 'suppliers.sync.config'],
                ['label' => 'Proveedores excluidos', 'route' => 'settings.suppliers.content.excluded-suppliers.index', 'permission' => 'suppliers.content.manage'],
                ['label' => 'Referencias excluidas', 'route' => 'settings.suppliers.sync.excluded-groups.index', 'permission' => 'suppliers.sync.config'],
                ['label' => 'Limpiar datos prueba', 'route' => 'settings.suppliers.sync.cleanup.index', 'permission' => 'suppliers.sync.status'],
            ],
        ]);

    }

    /**
     * Registrar Observers para detectar cambios en modelos
     *
     * Los observers disparan eventos cuando los modelos son modificados,
     * lo que inicia la sincronización bidireccional hacia Oracle.
     */
    protected function registerObservers(): void
    {
        SupplierProductPrice::observe(
            SupplierProductPriceObserver::class
        );

        // SupplierErpProvider::observe(
        //     SupplierErpProviderObserver::class
        // );

        Product::observe(
            SupplierProductObserver::class
        );

        SyncSetting::observe(
            SyncSettingObserver::class
        );

        AiContent::observe(
            AiContentVersionObserver::class
        );

        Category::observe(
            CategoryObserver::class
        );

        Sport::observe(
            SportObserver::class
        );

        AiBudget::observe(
            AiBudgetObserver::class
        );
    }

    /**
     * Registrar Event Listeners para sincronización bidireccional
     *
     * Los listeners reciben eventos y encolan jobs para sincronizar
     * cambios hacia Oracle en tiempo real.
     */
    protected function registerEventListeners(): void
    {
        // Registrar listeners para eventos de cambios en Supplier
        // $this->app['events']->listen(
        //     SupplierProductPriceChanged::class,
        //     SyncPriceToErpListener::class
        // );

        // $this->app['events']->listen(
        //     SupplierErpProviderUpdated::class,
        //     SyncProviderToErpListener::class
        // );

        $this->app['events']->listen(
            SupplierProductUpdated::class,
            SyncProductToErpListener::class
        );
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
