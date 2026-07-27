<?php

namespace Modules\HelpdeskIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Http\Middleware\Require2FA;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskIntegration\Console\Commands\PurgeIdentityVerificationsCommand;
use Modules\HelpdeskIntegration\Console\Commands\PurgeIntegrationAuditLogCommand;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Policies\IntegrationProviderPolicy;
use Modules\HelpdeskIntegration\Support\Drivers\ErpIntegrationDriver;
use Modules\HelpdeskIntegration\Support\Drivers\PrestashopIntegrationDriver;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskIntegrationServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskIntegration';

    protected string $nameLower = 'helpdeskintegration';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );

        $this->app->singleton(IntegrationDriverRegistry::class, fn () => new IntegrationDriverRegistry);
    }

    public function boot(): void
    {
        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Gate::policy(IntegrationProvider::class, IntegrationProviderPolicy::class);

        $this->registerDrivers();
        $this->registerRoutes();
        $this->registerNav();
        $this->registerCommands();
    }

    /**
     * Comandos de purga (identity verifications + audit log) y su
     * programacion diaria, siguiendo el patron de Mailer::PurgeMailerLogsCommand.
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PurgeIdentityVerificationsCommand::class,
            PurgeIntegrationAuditLogCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('helpdeskintegration:purge-identity-verifications')
                ->dailyAt('03:20')
                ->onOneServer()
                ->withoutOverlapping();

            $schedule->command('helpdeskintegration:purge-audit-log')
                ->weeklyOn(0, '03:40')
                ->onOneServer()
                ->withoutOverlapping();
        });
    }

    /**
     * Registra los drivers nativos guardados con class_exists(): si el modulo
     * satelite (HelpdeskPrestashop/HelpdeskErp) no esta instalado, la plataforma
     * simplemente no aparece como vinculable.
     */
    protected function registerDrivers(): void
    {
        $registry = $this->app->make(IntegrationDriverRegistry::class);

        if (class_exists(PrestashopContextService::class)) {
            $registry->register('prestashop', PrestashopIntegrationDriver::class);
        }

        if (class_exists(ErpContextService::class)) {
            $registry->register('erp', ErpIntegrationDriver::class);
        }
    }

    protected function registerRoutes(): void
    {
        $managers = module_path($this->name, 'routes/managers.php');

        if (file_exists($managers)) {
            Route::middleware(['web', 'auth', 'can:helpdesk.view', Require2FA::class])
                ->prefix('panel/helpdesk')
                ->group($managers);
        }

        $settings = module_path($this->name, 'routes/settings.php');

        if (file_exists($settings)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/settings/helpdeskintegration')
                ->name('settings.helpdeskintegration.')
                ->group($settings);
        }
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Integraciones externas',
            'items' => [
                [
                    'label' => 'Catálogo de proveedores',
                    'route' => 'settings.helpdeskintegration.providers.index',
                    'permission' => 'helpdeskintegration.providers.view',
                ],
            ],
        ]);
    }
}
