<?php

namespace Modules\HelpdeskEmailActivity\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskEmailActivity\Console\Commands\CheckEmailReputationCommand;
use Modules\HelpdeskEmailActivity\Console\Commands\ProcessEmailBouncesCommand;
use Modules\HelpdeskEmailActivity\Console\Commands\PruneEmailLogsCommand;
use Modules\HelpdeskEmailActivity\Console\Commands\UpdateCanIEmailDataCommand;
use Modules\HelpdeskEmailActivity\Listeners\EnforceEmailSuppression;
use Modules\HelpdeskEmailActivity\Listeners\LogEmailQueued;
use Modules\HelpdeskEmailActivity\Listeners\LogEmailSent;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Observers\EmailLogObserver;
use Modules\HelpdeskEmailActivity\Policies\EmailLogPolicy;
use Modules\HelpdeskEmailActivity\Services\EntityPanelRegistry;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskEmailActivityServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskEmailActivity';

    protected string $moduleNameLower = 'helpdeskemailactivity';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerListeners();
        $this->registerObservers();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerPolicies();
        $this->registerMenus();
    }

    protected function registerObservers(): void
    {
        EmailLog::observe(EmailLogObserver::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(EmailLog::class, EmailLogPolicy::class);
    }

    public function register(): void
    {
        // Singleton en register() (no boot()) a propósito: módulos satélite
        // (HelpdeskTickets, etc.) llaman a app(EntityPanelRegistry::class)
        // ->register(...) desde SU PROPIO boot() para registrar el panel de
        // su entidad — como todos los register() de todos los providers
        // corren antes que cualquier boot(), el singleton ya existe sin
        // importar el orden de carga de módulos. Ver el docblock de la
        // clase para el detalle completo.
        $this->app->singleton(EntityPanelRegistry::class);
    }

    protected function registerConfig(): void
    {
        $path = module_path($this->moduleName, 'config/config.php');

        $this->publishes([$path => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom($path, $this->moduleNameLower);
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'public') => public_path('modules/'.$this->moduleNameLower),
        ], $this->moduleNameLower.'-assets');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');
        $publishPath = resource_path('views/modules/'.$this->moduleNameLower);

        $this->publishes([$sourcePath => $publishPath], ['views', $this->moduleNameLower.'-module-views']);

        $published = array_values(array_filter(array_map(
            fn ($p) => is_dir($p.'/modules/'.$this->moduleNameLower) ? $p.'/modules/'.$this->moduleNameLower : null,
            config('view.paths', []),
        )));

        $this->loadViewsFrom([...$published, $sourcePath], $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->moduleName, 'routes/web.php'));
    }

    protected function registerListeners(): void
    {
        // Orden importa: LogEmailQueued primero (crea la fila 'queued' y
        // SIEMPRE deja pasar el evento, es void). EnforceEmailSuppression
        // corre después y puede cancelar el envío devolviendo false — ver
        // el docblock de esa clase para el porqué del orden.
        Event::listen(MessageSending::class, LogEmailQueued::class);
        Event::listen(MessageSending::class, EnforceEmailSuppression::class);
        Event::listen(MessageSent::class, LogEmailSent::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneEmailLogsCommand::class,
                ProcessEmailBouncesCommand::class,
                CheckEmailReputationCommand::class,
                // Sin schedule a propósito: el dataset se comitea (ver el
                // comando).
                UpdateCanIEmailDataCommand::class,
            ]);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('email-logs:prune')
                ->daily()
                ->at('03:30')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();

            // Mismo cron que documents:process-bounces antes (cada 10 min) —
            // el comando ya no-opea en silencio sin buzones habilitados, así
            // que no hace falta gatear el schedule con un Setting.
            $schedule->command('email-logs:process-bounces')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/email-log-bounces.log'));

            $schedule->command('email-logs:check-reputation')
                ->daily()
                ->at('06:00')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        });
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        // "Configuración" y "Reputación" se movieron por completo a Ajustes >
        // Helpdesk · Actividad de correo: son auditoría/config del envío, no
        // una bandeja de trabajo del día a día — ya no tienen sección propia
        // en el menú operativo de Helpdesk.
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Actividad de correo',
            'order' => 240,
            'items' => [
                [
                    'label' => 'Configuración',
                    'route' => 'settings.helpdeskemailactivity.index',
                    'permission' => 'helpdeskemailactivity.settings.view',
                ],
                [
                    // Distinto del anterior: aquel es la configuración
                    // (retención, etc.), este es el visor/historial real de
                    // los emails enviados con filtros y vistas guardadas.
                    'label' => 'Historial de envíos',
                    'route' => 'helpdeskemailactivity.index',
                    'permission' => 'helpdeskemailactivity.view',
                ],
                [
                    'label' => 'Reputación',
                    'route' => 'helpdeskemailactivity.reputation.index',
                    'permission' => 'helpdeskemailactivity.view',
                ],
                [
                    'label' => 'Buzones de rebote',
                    'route' => 'settings.helpdeskemailactivity.bounce-mailboxes.index',
                    'permission' => 'helpdeskemailactivity.settings.view',
                ],
                [
                    'label' => 'Lista de supresión',
                    'route' => 'settings.helpdeskemailactivity.suppressions.index',
                    'permission' => 'helpdeskemailactivity.settings.view',
                ],
            ],
        ]);
    }
}
