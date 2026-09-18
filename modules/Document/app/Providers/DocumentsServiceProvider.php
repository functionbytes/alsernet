<?php

namespace Modules\Document\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Document\Console\Commands\AnalyzeEmailJobErrors;
use Modules\Document\Console\Commands\CreateBlockedProductDocuments;
use Modules\Document\Console\Commands\InitializeDocumentWorkflows;
use Modules\Document\Console\Commands\MigrateProductBlockades;
use Modules\Document\Console\Commands\MonitorEmailJobs;
use Modules\Document\Console\Commands\ReinitializeDocumentWorkflows;
use Modules\Document\Console\Commands\RetryFailedEmailJobs;
use Modules\Document\Console\Commands\RevalidateDocumentTypes;
use Modules\Document\Console\Commands\SendDocumentUploadReminders;
use Modules\Document\Console\Commands\ValidateAllDocumentAspects;
use Modules\Document\Console\Commands\ValidateAndCreateDocumentsFromPaidOrders;
use Modules\Document\Console\Commands\ValidateDocumentMedia;
use Modules\Document\Console\Commands\ValidateDocumentsComplete;
use Modules\Document\Console\Commands\ValidateDocumentStages;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentPermission;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Http\ViewComposers\NavigationComposer;
use Modules\Document\Policies\DocumentPolicy;
use Modules\Document\Policies\SettingsPolicy;
use Modules\Document\Services\DocumentEmailLogPanelRenderer;
use Modules\Document\Services\PermissionService;
use Modules\HelpdeskEmailActivity\Services\EntityPanelRegistry;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocumentsServiceProvider extends ServiceProvider
{
    private const PERMISSION_NAMES_CACHE_KEY = 'document:permission-names';

    /** @var array<int, string>|null */
    private static ?array $permissionNames = null;

    use PathNamespace;

    protected string $name = 'Document';

    protected string $nameLower = 'documents';

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/documents.php',
            'documents'
        );

        $this->app->singleton(
            PermissionService::class,
            fn ($app) => new PermissionService
        );

        // Registrar DocumentPolicy
        $this->registerPolicies();
    }

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->registerMenus();
        $this->registerGates();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register routes directly (Laravel 12 compatible)
        $this->registerRoutes();

        $this->registerEmailLogPanel();
    }

    /**
     * Conecta el detalle de un email de HelpdeskEmailActivity con un panel propio
     * de este módulo: registra DocumentEmailLogPanelRenderer en el
     * EntityPanelRegistry de ese módulo (mismo patrón que
     * Modules\HelpdeskTickets\Providers\HelpdeskTicketsServiceProvider::registerEmailLogPanel())
     * para que un email cuyo entity_type sea Document::class muestre un
     * resumen propio sin que HelpdeskEmailActivity conozca este módulo.
     */
    protected function registerEmailLogPanel(): void
    {
        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        if (! class_exists(EntityPanelRegistry::class)) {
            return;
        }

        // TODO: falta este archivo en el repo (bug del commit b6103097) -
        // hasta que se cree, el panel de email queda desactivado en vez de
        // tumbar el arranque de la app.
        if (! class_exists(DocumentEmailLogPanelRenderer::class)) {
            return;
        }

        $this->app->make(EntityPanelRegistry::class)
            ->register(new DocumentEmailLogPanelRenderer);
    }

    /**
     * Registrar políticas de autorización
     */
    protected function registerPolicies(): void
    {
        if (class_exists(Document::class)) {
            Gate::policy(
                Document::class,
                DocumentPolicy::class
            );
        }
    }

    /**
     * Registrar gates para autorización de settings y documentos
     */
    protected function registerGates(): void
    {
        $settingsPolicy = new SettingsPolicy;

        // Configure settings gate
        Gate::define('configure-documents', fn ($user) => $settingsPolicy->configure($user));
        Gate::define('view-document-settings', fn ($user) => $settingsPolicy->viewSettings($user));
        Gate::define('manage-document-types', fn ($user) => $settingsPolicy->manageTypes($user));
        Gate::define('manage-document-conditions', fn ($user) => $settingsPolicy->manageConditions($user));
        Gate::define('manage-document-sla-policies', fn ($user) => $settingsPolicy->manageSLAPolicies($user));
        Gate::define('manage-document-groups', fn ($user) => $settingsPolicy->manageGroups($user));
        Gate::define('manage-document-blockades', fn ($user) => $settingsPolicy->manageBlockades($user));
        Gate::define('sync-document-blockades', fn ($user) => $settingsPolicy->syncBlockades($user));

        // 🔒 Seguridad (HD-DOC-01): permiso base para acceder a las rutas API autenticadas
        // del panel de documentos (modules/Document/routes/api.php). 'super-admin' se
        // bloquearía sin esto pese a que el permiso 'view-documents' ya lo cubriría vía
        // Gate::before, pero se resuelve aquí directamente para no depender del bypass
        // global. 'supervisor' se resuelve aquí (no en el Gate::before de abajo) para no
        // convertirlo en un bypass de super-admin para el resto de la aplicación.
        Gate::define('view-documents-panel', fn ($user) => $user->hasRole('supervisor') || $user->canDocument('view-documents'));

        // Register dynamic gates for document permissions
        // This allows using middleware('can:permission-name') with any permission from document_permissions table
        Gate::before(function ($user, $ability) {
            // Este gancho existe para resolver los permisos que viven en la
            // tabla `document_permissions`, que no son permisos de Spatie y por
            // eso hay que comprobarlos a mano contra los grupos validadores.
            //
            // El atajo de super-admin estaba ANTES de esa comprobación y sin
            // acotar por ability, así que concedía cualquier permiso de
            // cualquiera de los 40 módulos —no solo los de Document— a los 212
            // usuarios con ese rol: un Gate::before de un módulo satélite
            // decidiendo sobre todo el sistema. Ahora se responde únicamente
            // dentro del dominio propio, igual que hace Supplier con el suyo, y
            // fuera de aquí manda el permiso asignado.
            if (! class_exists('Modules\Document\Entities\DocumentPermission')) {
                return null;
            }

            // La comprobación de "¿es esto un permiso de Document?" era un
            // SELECT EXISTS contra document_permissions… en CADA can() de la
            // aplicación entera, no solo de este módulo. Medido en la bandeja
            // de conversaciones: 95 de sus 171 consultas eran esta, repetida,
            // para una tabla de 53 filas que cambia una vez al año. Ahora la
            // lista se resuelve una vez por petición (y se cachea 10 minutos)
            // y la comparación es en memoria.
            if (! in_array($ability, self::documentPermissionNames(), true)) {
                return null; // No es un permiso de Document: no opinamos.
            }

            if ($user->hasRole('super-admin')) {
                return true;
            }

            // Una sola consulta en vez de 1 (traer los grupos) + N (preguntar
            // grupo a grupo si tiene el permiso).
            return DocumentValidatorGroup::query()
                ->whereHas('users', fn ($q) => $q->where('user_id', $user->id))
                ->whereHas('permissions', fn ($q) => $q->where('name', $ability))
                ->exists();
        });
    }

    /**
     * Nombres de los permisos que gobierna este módulo.
     *
     * Memo estático por petición + caché corta: el Gate::before de arriba se
     * consulta decenas de veces por pantalla y esta lista es de 53 filas que
     * solo cambian cuando se instala o amplía el módulo. La caché se limpia
     * desde DocumentPermissionsCache::forget() al tocar los permisos.
     *
     * @return array<int, string>
     */
    public static function documentPermissionNames(): array
    {
        if (self::$permissionNames !== null) {
            return self::$permissionNames;
        }

        return self::$permissionNames = Cache::remember(
            self::PERMISSION_NAMES_CACHE_KEY,
            600,
            fn () => DocumentPermission::query()->pluck('name')->all()
        );
    }

    /**
     * Invalida la lista cacheada (crear/borrar permisos de Document).
     */
    public static function forgetPermissionNames(): void
    {
        self::$permissionNames = null;
        Cache::forget(self::PERMISSION_NAMES_CACHE_KEY);
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        // Web routes (operational and configuration) - middleware/prefix/name applied within the file
        require module_path($this->name, 'routes/web.php');

        // API routes - middleware applied individually in routes/api.php
        // ✅ NO middleware applied here to avoid conflicts between stateless 'api' and session-based 'web' middleware
        // ✅ routes/api.php defines separate groups: public routes use 'api', authenticated routes use 'web'
        Route::prefix('api/documents')
            ->name('api.documents.')
            ->group(function () {
                require module_path($this->name, 'routes/api.php');
            });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SendDocumentUploadReminders::class,
            InitializeDocumentWorkflows::class,
            MigrateProductBlockades::class,
            CreateBlockedProductDocuments::class,
            ValidateAndCreateDocumentsFromPaidOrders::class,
            RevalidateDocumentTypes::class,
            ValidateDocumentStages::class,
            ValidateAllDocumentAspects::class,
            ValidateDocumentMedia::class,
            ValidateDocumentsComplete::class,
            ReinitializeDocumentWorkflows::class,
            MonitorEmailJobs::class,
            AnalyzeEmailJobErrors::class,
            RetryFailedEmailJobs::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Send document reminders - every 10 minutes
            $schedule->command('documents:send-reminders')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/document-reminders.log'));

            // Blockade sync - dynamic schedule based on DB settings
            $this->registerBlockadeSyncSchedule($schedule);

            // El procesado de rebotes se centralizó en HelpdeskEmailActivity
            // (email-logs:process-bounces, ver
            // Modules\HelpdeskEmailActivity\Providers\HelpdeskEmailActivityServiceProvider)
            // — cubre el buzón de Document generalizado a una lista de
            // buzones gestionable, en vez de un único Setting fijo aquí. Si
            // este entorno tenía documents.bounce_imap_* configurado, hay
            // que migrarlo a esa pantalla (Settings → Log de emails →
            // Buzones de rebote) antes de desplegar este cambio — esos
            // Settings ya no los lee nadie.
        });
    }

    /**
     * Register the blockade sync schedule based on saved configuration.
     */
    protected function registerBlockadeSyncSchedule(Schedule $schedule): void
    {
        try {
            $setting = Setting::get('documents.blockade_sync_enabled', 'no');
            if ($setting !== 'yes') {
                return;
            }

            $frequency = Setting::get('documents.blockade_sync_frequency', 'manual');
            if ($frequency === 'manual') {
                return;
            }

            $hourRaw = Setting::get('documents.blockade_sync_hour', '08:00');
            $cronExpr = Setting::get('documents.blockade_sync_cron', '');
            $fresh = Setting::get('documents.blockade_sync_fresh', 'no') === 'yes';
            $args = $fresh ? ['--fresh' => true] : [];

            [$h, $m] = array_map('intval', explode(':', $hourRaw.':00'));

            $command = $schedule->command('migrate:product-blockades', $args)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/document-blockade-sync.log'));

            match ($frequency) {
                'hourly' => $command->hourlyAt($m),
                'every_2_hours' => $command->everyTwoHours($m),
                'every_6_hours' => $command->everySixHours($m),
                'every_12_hours' => $command->twiceDailyAt($h, ($h + 12) % 24, $m),
                'daily' => $command->dailyAt($hourRaw),
                'weekly' => $command->weeklyOn(0, $hourRaw),
                'custom' => $cronExpr ? $command->cron($cronExpr) : null,
                default => null,
            };
        } catch (\Exception $e) {
            // No interrumpir el boot si la tabla settings aún no existe
        }
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

    protected function registerViewComposers(): void
    {
        // Register navigation composer for theme layout
        view()->composer(
            'theme.components.nav',
            NavigationComposer::class
        );
    }

    /**
     * Registrar menús del módulo Document
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Documentos (operaciones)
        NavService::registerMiniItem('documents', [
            'icon' => 'wallet',
            'tooltip' => 'Documentos',
            'sidebar_id' => 'documents',
            'order' => 20,
        ]);

        // Sidebar local - Documentos (operaciones)
        NavService::registerSidebar('documents', [
            'title' => 'Documentos',
            'items' => [
                ['label' => 'Listado de documentos', 'route' => 'documents.index', 'permission' => 'route-all-documents'],
                ['label' => 'Documentos pendientes', 'route' => 'documents.pending', 'permission' => 'route-pending-documents|approve-documents|reject-documents'],
            ],
        ]);

        // Agregar configuraciones de documentos al sidebar genérico 'settings'
        NavService::registerSidebar('settings', [
            'title' => 'Documentos',
            'order' => 110,
            'items' => [
                ['label' => 'Configuración global', 'route' => 'settings.documents.configurations.global'],
                ['label' => 'Almacenamiento', 'route' => 'settings.documents.configurations.storage'],
                ['label' => 'Tipos de documento', 'route' => 'settings.documents.types.index'],
                ['label' => 'Condiciones de validación', 'route' => 'settings.documents.conditions.index'],
                ['label' => 'Políticas SLA', 'route' => 'settings.documents.sla-policies.index'],
                ['label' => 'Grupos de validadores', 'route' => 'settings.documents.groups.index'],
                ['label' => 'Bloqueos de productos', 'route' => 'settings.documents.blockades.index'],
                ['label' => 'Sincronización de bloqueos', 'route' => 'settings.documents.configurations.sync-schedule'],
                ['label' => 'Endpoints / Integraciones', 'route' => 'settings.documents.configurations.endpoints'],
            ],
        ]);
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
