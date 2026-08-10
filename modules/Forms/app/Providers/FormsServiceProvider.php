<?php

namespace Modules\Forms\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class FormsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Forms';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'forms'
        );
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'forms');
        $this->registerRoutes();
        $this->registerNav();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('forms.php'),
        ], 'config');
    }

    protected function registerRoutes(): void
    {
        // Webhook receiver: autenticado vía HMAC (VerifyAlsernetFormsHmac), no Sanctum.
        // Mismo esquema que api/helpdeskprestashop/webhooks (HelpdeskPrestashop).
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/forms/webhooks')
            ->name('api.forms.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));

        // Panel de gestión: reporte (solo lectura) + CRUD de formularios.
        // Reutiliza los permisos ya existentes de HelpdeskTickets en vez de
        // sembrar unos nuevos solo para estas pantallas: 'view' para
        // ver el reporte/listado, 'settings' (más restrictivo, dentro de las
        // rutas del propio managers.php) para crear/editar/activar.
        $managers = module_path($this->moduleName, 'routes/managers.php');

        if (file_exists($managers)) {
            Route::middleware(['web', 'auth', 'can:helpdesk.tickets.view'])
                ->prefix('panel/forms')
                ->group($managers);
        }
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! function_exists('helpdesk_forms_enabled') || ! helpdesk_forms_enabled()) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Reporte de formularios', 'route' => 'forms.report.index', 'permission' => 'helpdesk.tickets.view'],
                ['label' => 'Gestionar formularios', 'route' => 'forms.manage.index', 'permission' => 'helpdesk.tickets.settings'],
            ],
        ]);
    }
}
