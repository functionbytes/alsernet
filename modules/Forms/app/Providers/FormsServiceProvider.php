<?php

namespace Modules\Forms\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Forms\Console\Commands\CheckStoreDefinitionsCommand;
use Modules\Forms\Console\Commands\CleanupAbandonedFormsCommand;
use Modules\Forms\Console\Commands\CleanupFormDataCommand;
use Modules\Forms\Console\Commands\CleanupFormTokensCommand;
use Modules\Forms\Console\Commands\DrainStoreOutboxCommand;
use Modules\Forms\Console\Commands\FormsInstallCommand;
use Modules\Forms\Console\Commands\ImportStoreFormsCommand;
use Modules\Forms\Console\Commands\ProcessFormFollowUpsCommand;
use Modules\Forms\Console\Commands\PruneFormVersionsCommand;
use Modules\Forms\Console\Commands\SendAbandonReminderCommand;
use Modules\Forms\Models\Form;
use Modules\Forms\Policies\FormPolicy;
use Modules\Forms\Services\FormAnalyticsService;
use Modules\Forms\Services\FormEmailService;
use Modules\Forms\Services\FormJsonLdGenerator;
use Modules\Forms\Services\FormPageCacheInvalidator;
use Modules\Forms\Services\FormService;
use Modules\Forms\Services\FormSubmissionService;
use Modules\Forms\Services\FormValidationBuilder;
use Modules\Forms\Services\FormWebhookService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

/**
 * El módulo Forms cubre dos cosas distintas que comparten namespace:
 *
 *  1. El constructor de formularios (Form, FormField, FormSubmission...):
 *     back-office en panel/forms + inbox de envíos + páginas públicas
 *     /forms/{slug} y /forms/embed/{slug}.
 *
 *  2. El receptor de alsernetforms (AlsernetForm, tabla helpdesk_forms):
 *     webhook firmado por HMAC desde el sitio Alvarez que abre un ticket de
 *     Helpdesk por cada envío, más sus pantallas de gestión y reporte bajo
 *     panel/helpdesk/settings/tickets.
 *
 * Son independientes: distintas tablas, distintas rutas, distintos permisos.
 * Conviven aquí porque ambos son "formularios" de cara al usuario del panel.
 */
class FormsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Forms';

    protected string $moduleNameLower = 'forms';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );

        $this->app->singleton(FormEmailService::class);
        $this->app->singleton(FormWebhookService::class);
        $this->app->singleton(FormSubmissionService::class);
        $this->app->singleton(FormPageCacheInvalidator::class);
        $this->app->singleton(FormService::class);
        $this->app->singleton(FormValidationBuilder::class);
        $this->app->singleton(FormAnalyticsService::class);
        $this->app->singleton(FormJsonLdGenerator::class);
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRateLimiters();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerMenus();
        $this->registerCommands();
        $this->registerScheduledTasks();
    }

    /**
     * Rate limiter combinado IP+form_slug para evitar bloquear a un usuario
     * legítimo que usa varios formularios distintos en la misma sesión.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('forms.submit', function (Request $request) {
            $slug = $request->route('slug') ?? 'unknown';
            $perFormLimit = (int) config('forms.throttle_submissions', 20);

            return [
                Limit::perMinute(5)->by($request->ip().'|'.$slug),
                Limit::perHour($perFormLimit)->by($request->ip()),
            ];
        });

        RateLimiter::for('forms.public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }

    /**
     * Comandos de mantenimiento del constructor. Asume que el host corre
     * `php artisan schedule:run` cada minuto.
     */
    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            /* La tienda encola cada envío en `alsernet_requests` y su cron es
               quien lo empuja, pero ese contenedor no lleva demonio cron: se
               dispara desde aquí. Sin esto, un envío que llegue con el panel
               caído se queda en la cola y no sale nunca -- llegaron a
               acumularse 119 peticiones, la más vieja de febrero. Mismo patrón
               que reviews:drain-store y questions:drain-store. */
            $schedule->command(DrainStoreOutboxCommand::class)
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command(ProcessFormFollowUpsCommand::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command(SendAbandonReminderCommand::class)
                ->hourly()
                ->withoutOverlapping();

            $schedule->command(CleanupAbandonedFormsCommand::class)
                ->dailyAt('03:15')
                ->withoutOverlapping();

            $schedule->command(CleanupFormDataCommand::class)
                ->dailyAt('03:30')
                ->withoutOverlapping();

            $schedule->command(CleanupFormTokensCommand::class)
                ->dailyAt('03:45')
                ->withoutOverlapping();

            /* Deriva entre panel y tienda (p. ej. tras restaurar una copia
               de seguridad de PrestaShop): solo avisa, no republica solo. */
            $schedule->command(CheckStoreDefinitionsCommand::class)
                ->dailyAt('06:00')
                ->withoutOverlapping();

            $schedule->command(PruneFormVersionsCommand::class)
                ->weekly()
                ->sundays()
                ->at('04:00')
                ->withoutOverlapping();
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
    }

    protected function registerTranslations(): void
    {
        foreach (['lang', 'resources/lang'] as $relative) {
            $langPath = module_path($this->moduleName, $relative);

            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            }
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        // ─── Constructor de formularios ──────────────────────────────────────
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));

        // ─── Receptor de alsernetforms ───────────────────────────────────────
        // Webhook: autenticado vía HMAC (VerifyAlsernetFormsHmac), no Sanctum.
        // Mismo esquema que api/helpdeskprestashop/webhooks (HelpdeskPrestashop).
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/forms/webhooks')
            ->name('api.forms.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));

        // Panel de gestión: reporte (solo lectura) + CRUD de formularios.
        // Reutiliza los permisos ya existentes de HelpdeskTickets en vez de
        // sembrar unos nuevos solo para estas pantallas: 'view' para ver el
        // reporte/listado, 'settings' (más restrictivo, dentro de las rutas del
        // propio managers.php) para crear/editar/activar.
        //
        // Prefijo bajo panel/helpdesk/settings/tickets/: un formulario mapea 1:1
        // a una categoría de ticket (ver AlsernetForm::category()), así que esta
        // pantalla vive junto a Categorías/Automatizaciones/Respuestas
        // predefinidas en vez de como sección propia.
        $managers = module_path($this->moduleName, 'routes/managers.php');

        if (file_exists($managers)) {
            Route::middleware(['web', 'auth', 'can:helpdesk.tickets.view'])
                ->prefix('panel/helpdesk/settings/tickets')
                ->group($managers);
        }
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        $this->registerBuilderMenus();
        $this->registerAlsernetMenus();
    }

    /**
     * Rail + sidebar del constructor de formularios.
     */
    protected function registerBuilderMenus(): void
    {
        NavService::registerMiniItem('forms-inbox', [
            'icon' => 'inbox',
            'tooltip' => 'Formularios',
            'sidebar_id' => 'forms-inbox',
            'order' => 46,
            'permission' => 'Forms.inbox.index|Forms.submissions.index',
        ]);

        NavService::registerSidebar('forms-inbox', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'forms.inbox.dashboard', 'permission' => 'Forms.inbox.index'],
                ['label' => 'Todas las submissions', 'route' => 'forms.inbox.index', 'permission' => 'Forms.submissions.index'],
                ['label' => 'Todos los formularios', 'route' => 'settings.forms.index', 'permission' => 'Forms.forms.index'],
                ['label' => 'Categorias', 'route' => 'settings.forms.categories.index', 'permission' => 'Forms.categories.manage'],
                ['label' => 'Biblioteca de plantillas', 'route' => 'settings.forms.templates-library.index', 'permission' => 'Forms.templates.manage'],
            ],
        ]);
    }

    /**
     * Entradas del receptor de alsernetforms.
     *
     * Se fusionan en la sección 'Helpdesk · Tickets' que registra
     * HelpdeskServiceProvider (mismo título -> NavService::registerSidebar las
     * une, sin importar qué proveedor arranque primero): un formulario de
     * alsernetforms mapea 1:1 a una categoría de ticket, así que vive junto a
     * Categorías/Automatizaciones/Respuestas predefinidas.
     */
    protected function registerAlsernetMenus(): void
    {
        if (! function_exists('helpdesk_forms_enabled') || ! helpdesk_forms_enabled()) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Tickets',
            'order' => 220,
            'items' => [
                ['label' => 'Formularios del sitio', 'route' => 'forms.manage.index', 'permission' => 'helpdesk.tickets.settings'],
                ['label' => 'Reporte de formularios', 'route' => 'forms.report.index', 'permission' => 'helpdesk.tickets.view'],
            ],
        ]);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessFormFollowUpsCommand::class,
                SendAbandonReminderCommand::class,
                CleanupFormDataCommand::class,
                FormsInstallCommand::class,
                CleanupAbandonedFormsCommand::class,
                CleanupFormTokensCommand::class,
                PruneFormVersionsCommand::class,
                ImportStoreFormsCommand::class,
                DrainStoreOutboxCommand::class,
                CheckStoreDefinitionsCommand::class,
            ]);
        }
    }

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
