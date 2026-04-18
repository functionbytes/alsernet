<?php

namespace Modules\Attention\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Attention\Console\Commands\ArchiveOldAttentionsCommand;
use Modules\Attention\Console\Commands\MigrateAttentionDataCommand;
use Modules\Attention\Console\Commands\PopulateColombianHolidaysCommand;
use Modules\Attention\Console\Commands\ValidateAttentionDeletionCommand;
use Modules\Attention\Models\Attention;
use Modules\Attention\Policies\AttentionPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class AttentionServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Attention';

    protected string $moduleNameLower = 'attention';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if (Module::find('Attention')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerMenus();
    }

    /**
     * Register the module policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Attention::class, AttentionPolicy::class);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateAttentionDataCommand::class,
                PopulateColombianHolidaysCommand::class,
                ArchiveOldAttentionsCommand::class,
                ValidateAttentionDeletionCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }

    /**
     * Registrar menus del modulo Attention
     */
    protected function registerMenus(): void
    {
        NavService::registerMiniItem('attentions', [
            'icon' => 'fa-duotone fa-headset',
            'tooltip' => 'Atenciones peticiones',
            'sidebar_id' => 'attentions',
            'order' => 45,
        ]);

        // Sidebar principal de Atenciones (gestion peticiones)
        NavService::registerSidebar('attentions', [
            'title' => 'Gestion peticiones',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'attention.dashboard'],
                ['label' => 'Pendientes', 'route' => 'attention.pending'],
                ['label' => 'Seguimiento', 'route' => 'attention.tracking'],
            ],
        ]);

        // Configuraciones de Atenciones en el sidebar de Settings
        NavService::registerSidebar('settings', [
            'title' => 'Atenciones',
            'items' => [
                ['label' => 'Configuracion general', 'route' => 'settings.attention.configurations.global'],
                ['label' => 'Tipos', 'route' => 'settings.attention.types.index'],
                ['label' => 'Categorias', 'route' => 'settings.attention.categories.index'],
                ['label' => 'Departamentos', 'route' => 'settings.attention.departments.index'],
                ['label' => 'Sedes', 'route' => 'settings.attention.sedes.index'],
                ['label' => 'Politicas SLA', 'route' => 'settings.attention.sla-policies.index'],
                ['label' => 'Plantillas email', 'route' => 'settings.attention.templates.index'],
                ['label' => 'Reglas de asignacion', 'route' => 'settings.attention.routing-rules.index'],
            ],
        ]);
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
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
