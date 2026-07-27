<?php

namespace Modules\Document\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
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
use Modules\Document\Console\Commands\RotateWeakDocumentUids;
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
use Modules\Document\Services\PermissionService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocumentsServiceProvider extends ServiceProvider
{
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

        // Acceso al panel operativo /panel/documents (listado, pendientes, ver por UID, etc.)
        // Reemplaza el antiguo middleware('role:super-admin|supervisor'): ese middleware de
        // Spatie hace un hasAnyRole() directo y NUNCA pasa por Gate::before, así que los
        // miembros de grupos validadores (licenses_team, documentation_team...) quedaban
        // bloqueados pese a que el permiso 'view-documents' ya los cubre. 'supervisor' se
        // resuelve aquí (no en el Gate::before global de abajo) para no convertirlo en un
        // bypass de super-admin para el resto de la aplicación.
        Gate::define('view-documents-panel', fn ($user) => $user->hasRole('supervisor') || $user->canDocument('view-documents'));

        // Register dynamic gates for document permissions
        // This allows using middleware('can:permission-name') with any permission from document_permissions table
        Gate::before(function ($user, $ability) {
            // Super-admin bypass
            if ($user->hasRole('super-admin')) {
                return true;
            }

            // Check if this ability exists in document permissions
            if (class_exists('Modules\Document\Entities\DocumentPermission')) {
                $permission = DocumentPermission::where('name', $ability)->first();

                if ($permission) {
                    // Get user's validator groups
                    $userGroups = DocumentValidatorGroup::whereHas('users', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->get();

                    // Check if any of the user's groups have this permission
                    foreach ($userGroups as $group) {
                        if ($group->permissions()->where('name', $ability)->exists()) {
                            return true;
                        }
                    }

                    return false;
                }
            }

            return null; // Let other gates/policies handle it
        });
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
            RotateWeakDocumentUids::class,
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

        $this->publishes([
            module_path($this->name, 'public') => public_path('modules/'.$this->nameLower),
        ], ['public', $this->nameLower.'-module-assets']);

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
            'icon' => 'fa-duotone fa-thin fa-album-collection-circle-plus',
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
