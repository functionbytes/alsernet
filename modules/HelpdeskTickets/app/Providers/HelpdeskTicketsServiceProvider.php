<?php

namespace Modules\HelpdeskTickets\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Events\ConversationMarkedAsSpam;
use Modules\HelpdeskEmailActivity\Services\EntityPanelRegistry;
use Modules\HelpdeskTickets\Console\Commands\AutoCloseTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\AutoResponseTicketCommand;
use Modules\HelpdeskTickets\Console\Commands\CleanupTrashedTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\CollectOpsMetricsCommand;
use Modules\HelpdeskTickets\Console\Commands\DetectTicketIncidentsCommand;
use Modules\HelpdeskTickets\Console\Commands\FetchEmailTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\MarkOverdueTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\PruneBlacklistHitsCommand;
use Modules\HelpdeskTickets\Console\Commands\PublishHelpdeskTicketsAssetsCommand;
use Modules\HelpdeskTickets\Console\Commands\ReviewTicketQualityCommand;
use Modules\HelpdeskTickets\Console\Commands\SendDueTicketFollowupsCommand;
use Modules\HelpdeskTickets\Console\Commands\SendScheduledRepliesCommand;
use Modules\HelpdeskTickets\Console\Commands\SendScheduledReportsCommand;
use Modules\HelpdeskTickets\Console\Commands\SendScheduledTicketMailsCommand;
use Modules\HelpdeskTickets\Console\Commands\SendSlaWarnings as SendSlaWarningsCommand;
use Modules\HelpdeskTickets\Console\Commands\SimulateIncomingTicketEmailsCommand;
use Modules\HelpdeskTickets\Console\Commands\SuggestHelpArticlesCommand;
use Modules\HelpdeskTickets\Http\Controllers\Dev\EmailTestController;
use Modules\HelpdeskTickets\Jobs\AutoAssignUnassignedTickets;
use Modules\HelpdeskTickets\Jobs\CheckSlaBreaches;
use Modules\HelpdeskTickets\Jobs\CleanupOldTickets;
use Modules\HelpdeskTickets\Jobs\EscalateTicketsJob;
use Modules\HelpdeskTickets\Jobs\ProcessRecurringTicketsJob;
use Modules\HelpdeskTickets\Jobs\SendSlaWarnings;
use Modules\HelpdeskTickets\Listeners\AddSpamSenderToBlacklist;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Observers\TicketObserver;
use Modules\HelpdeskTickets\Policies\AutomationPolicy;
use Modules\HelpdeskTickets\Policies\MacroPolicy;
use Modules\HelpdeskTickets\Policies\RecurringTicketPolicy;
use Modules\HelpdeskTickets\Policies\SlaPolicyPolicy;
use Modules\HelpdeskTickets\Policies\TicketCannedReplyPolicy;
use Modules\HelpdeskTickets\Policies\TicketCategoryPolicy;
use Modules\HelpdeskTickets\Policies\TicketCommentPolicy;
use Modules\HelpdeskTickets\Policies\TicketGroupPolicy;
use Modules\HelpdeskTickets\Policies\TicketMailPolicy;
use Modules\HelpdeskTickets\Policies\TicketNotePolicy;
use Modules\HelpdeskTickets\Policies\TicketPolicy;
use Modules\HelpdeskTickets\Policies\TicketStatusPolicy;
use Modules\HelpdeskTickets\Policies\TicketTemplatePolicy;
use Modules\HelpdeskTickets\Policies\TicketViewPolicy;
use Modules\HelpdeskTickets\Policies\TimeEntryPolicy;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Services\Compliance\TicketGdprExportContributor;
use Modules\HelpdeskTickets\Services\EscalationService;
use Modules\HelpdeskTickets\Services\SlaService;
use Modules\HelpdeskTickets\Services\TicketEmailLogPanelRenderer;
use Modules\HelpdeskTickets\Services\TicketService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskTicketsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskTickets';

    protected string $moduleNameLower = 'helpdesktickets';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerMenus();
        $this->registerEventListeners();
        $this->registerRateLimiters();
        $this->registerEmailLogPanel();

        // Seccion 'tickets' del export GDPR (derecho de acceso). Igual que la
        // cascada de borrado, NO se ata al toggle de integracion: es una
        // obligacion legal mientras el modulo (y sus datos) esten instalados.
        $this->app->tag([TicketGdprExportContributor::class], GdprExportContributor::TAG);

        Ticket::observe(TicketObserver::class);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(ConversationMarkedAsSpam::class, AddSpamSenderToBlacklist::class);
    }

    /**
     * Conecta la ficha del ticket con el panel de auditoría de
     * HelpdeskEmailActivity: registra TicketEmailLogPanelRenderer en el
     * EntityPanelRegistry de ese módulo para que el detalle de un email
     * cuyo entity_type sea Ticket::class muestre un mini-resumen de
     * "tickets relacionados del mismo cliente" sin que HelpdeskEmailActivity
     * necesite conocer a HelpdeskTickets (la dependencia va siempre en
     * sentido satélite → HelpdeskEmailActivity, nunca al revés — ver el
     * docblock de EmailLogEntityPanelRenderer).
     *
     * Se hace en boot() (no register()) a propósito: EntityPanelRegistry
     * se registra como singleton en el register() de
     * HelpdeskEmailActivityServiceProvider, y en Laravel todos los register()
     * de todos los providers corren antes que cualquier boot() — así este
     * boot() puede resolverlo sin preocuparse del orden de carga entre
     * módulos.
     *
     * Doble guarda antes de tocar el registro: helpdesk_emaillog_enabled()
     * (mismo patrón que el resto del provider con
     * helpdesk_tickets_enabled()) cubre el toggle de integración en
     * Settings, y class_exists() cubre que el módulo esté directamente
     * desinstalado/desactivado — con cualquiera de las dos en falso, el
     * autoloader ni siquiera necesita resolver la clase.
     */
    protected function registerEmailLogPanel(): void
    {
        if (! helpdesk_emaillog_enabled()) {
            return;
        }

        if (! class_exists(EntityPanelRegistry::class)) {
            return;
        }

        $this->app->make(EntityPanelRegistry::class)
            ->register(new TicketEmailLogPanelRenderer);
    }

    /**
     * routes/public.php referencia 'throttle:helpdesk-feedback' desde que se
     * creó la ruta pública de feedback, pero nunca se registró el limiter:
     * cualquier visita a /helpdesk/feedback/{ticketNumber} tiraba
     * MissingRateLimiterException (500), firmada o no (confirmado
     * 30-ago-2026 al investigar FeedbackSignedUrlTest — los 9 tests del
     * archivo daban 500/403 mal, no solo el bug de vista ya documentado).
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('helpdesk-feedback', fn ($request) => Limit::perMinute(30)
            ->by($request->ip()));
    }

    protected function registerMenus(): void
    {
        if (! helpdesk_tickets_enabled()) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Tickets',
            'items' => [
                [
                    // La bandeja global propia (helpdesk_ticket_mails) se
                    // retiró: este nombre de ruta ahora es un redirect hacia
                    // /panel/helpdeskemailactivity?module=HelpdeskTickets (mismo
                    // dato, auditoría cross-módulo unificada) — el
                    // "responder/redactar" que sí era exclusivo de tickets se
                    // reubicó dentro de la ficha del ticket (TicketsCrudController::showFull).
                    //
                    // 'permission' cambiado de 'helpdesk.tickets.emails.view' a
                    // 'helpdeskemailactivity.view': la autorización real la impone el
                    // destino del redirect (EmailLogController::index() ->
                    // authorize('viewAny', EmailLog::class) -> EmailLogPolicy,
                    // que exige exactamente ese permiso), no la ruta de origen.
                    // Con el permiso viejo, quien tuviera SOLO
                    // 'helpdesk.tickets.emails.view' veía el ítem, hacía clic, y
                    // se llevaba un 403 al llegar al redirect. La migración
                    // 2026_09_01_000000_grant_email_log_view_to_ticket_email_permission_holders
                    // (módulo HelpdeskEmailActivity) hace el backfill de quien ya
                    // tenía el permiso viejo asignado.
                    'label' => 'Emails enviados',
                    'route' => 'manager.helpdesk.tickets.emails.index',
                    'icon' => 'fas fa-paper-plane',
                    'permission' => 'helpdeskemailactivity.view',
                ],
                [
                    // Único pedazo de la antigua bandeja global que SÍ sigue
                    // siendo una pantalla propia de tickets — "programado, aún
                    // no enviado" es estado de trabajo, no auditoría de un
                    // envío que ya ocurrió (lo que sí cubre helpdeskemailactivity).
                    'label' => 'Programados',
                    'route' => 'manager.helpdesk.tickets.scheduled',
                    'icon' => 'fas fa-clock',
                    'permission' => 'helpdesk.tickets.emails.view',
                ],
                [
                    'label' => 'Listado de tickets',
                    'route' => 'manager.helpdesk.tickets.index',
                    'icon' => 'fas fa-ticket',
                    'permission' => 'helpdesk.tickets.view',
                ],
                // "Tickets recurrentes" se movió a Ajustes > Helpdesk · Tickets
                // (es una regla de configuración — cada cuánto se generan
                // tickets — no una bandeja de trabajo del día a día).
                [
                    'label' => 'Plantillas',
                    'route' => 'manager.helpdesk.ticket-templates.index',
                    'icon' => 'fas fa-file-lines',
                    'permission' => 'helpdesk.tickets.manage',
                ],
                [
                    // Antes vivía en la sección "Reportes" del Helpdesk core,
                    // mezclado con reportes de conversaciones (CSAT, Clientes
                    // en riesgo) que nada tienen que ver con tickets. Al
                    // registrarse aquí, además, el enlace desaparece del menú
                    // cuando el módulo está desactivado en vez de quedar
                    // apuntando a una pantalla "no disponible".
                    'label' => 'Incumplimientos SLA',
                    'route' => 'manager.helpdesk.reports.sla-breaches',
                    'icon' => 'fas fa-gauge-high',
                    'permission' => 'helpdesk.reports.view',
                ],
            ],
        ]);
    }

    public function register(): void
    {
        $this->app->register(HelpdeskTicketsEventServiceProvider::class);

        $this->app->singleton(TicketService::class);
        $this->app->singleton(TicketUpdateService::class);
        $this->app->singleton(SlaService::class);
        $this->app->singleton(AssignmentService::class);
        $this->app->singleton(EscalationService::class);
        $this->app->singleton(AutomationEngine::class);
    }

    protected function registerPolicies(): void
    {
        $map = [
            Ticket::class => TicketPolicy::class,
            TicketComment::class => TicketCommentPolicy::class,
            TicketNote::class => TicketNotePolicy::class,
            RecurringTicket::class => RecurringTicketPolicy::class,
            TicketTemplate::class => TicketTemplatePolicy::class,
            TicketTimeEntry::class => TimeEntryPolicy::class,
            TicketStatus::class => TicketStatusPolicy::class,
            TicketCategory::class => TicketCategoryPolicy::class,
            TicketGroup::class => TicketGroupPolicy::class,
            Macro::class => MacroPolicy::class,
            Automation::class => AutomationPolicy::class,
            TicketSlaPolicy::class => SlaPolicyPolicy::class,
            TicketCannedReply::class => TicketCannedReplyPolicy::class,
            TicketView::class => TicketViewPolicy::class,
            TicketMail::class => TicketMailPolicy::class,
        ];

        foreach ($map as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = array_values(array_filter([
            class_exists(AutoCloseTicketsCommand::class) ? AutoCloseTicketsCommand::class : null,
            class_exists(MarkOverdueTicketsCommand::class) ? MarkOverdueTicketsCommand::class : null,
            class_exists(AutoResponseTicketCommand::class) ? AutoResponseTicketCommand::class : null,
            class_exists(CleanupTrashedTicketsCommand::class) ? CleanupTrashedTicketsCommand::class : null,
            class_exists(PruneBlacklistHitsCommand::class) ? PruneBlacklistHitsCommand::class : null,
            class_exists(FetchEmailTicketsCommand::class) ? FetchEmailTicketsCommand::class : null,
            class_exists(SendSlaWarningsCommand::class) ? SendSlaWarningsCommand::class : null,
            class_exists(CollectOpsMetricsCommand::class) ? CollectOpsMetricsCommand::class : null,
            class_exists(DetectTicketIncidentsCommand::class) ? DetectTicketIncidentsCommand::class : null,
            class_exists(SuggestHelpArticlesCommand::class) ? SuggestHelpArticlesCommand::class : null,
            class_exists(ReviewTicketQualityCommand::class) ? ReviewTicketQualityCommand::class : null,
            class_exists(SendDueTicketFollowupsCommand::class) ? SendDueTicketFollowupsCommand::class : null,
            class_exists(SendScheduledRepliesCommand::class) ? SendScheduledRepliesCommand::class : null,
            class_exists(SendScheduledReportsCommand::class) ? SendScheduledReportsCommand::class : null,
            class_exists(SendScheduledTicketMailsCommand::class) ? SendScheduledTicketMailsCommand::class : null,
            class_exists(SimulateIncomingTicketEmailsCommand::class) ? SimulateIncomingTicketEmailsCommand::class : null,
            class_exists(PublishHelpdeskTicketsAssetsCommand::class) ? PublishHelpdeskTicketsAssetsCommand::class : null,
        ]));

        if ($commands) {
            $this->commands($commands);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $enabled = fn () => helpdesk_tickets_enabled();

            $schedule->command('imap:emailticket')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('ticket:autoclose')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('ticket:autooverdue')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('ticket:autoresponseticket')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('trashedticket:autodelete')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('ticket:send-followups')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('ticket:send-scheduled-replies')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);
            $schedule->command('helpdesk:send-scheduled-emails')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);

            // Picos de tickets sobre un mismo problema. Cada 10 min y no cada
            // minuto: una incidencia masiva no se forma en 60 segundos, y cada
            // pasada recorre vectores en PHP. El comando re-verifica su propio
            // toggle (helpdeskagents.ticket_similarity.enabled, OFF por defecto).
            $schedule->command('helpdesk:detect-incidents')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->when(fn () => $enabled() && (bool) config('helpdeskagents.ticket_similarity.enabled', false));

            // Borradores de artículo a partir de tickets resueltos repetidos.
            // Semanal: es un barrido de un mes de tickets, no algo que cambie
            // de un día para otro.
            $schedule->command('helpdesk:suggest-articles')
                ->weeklyOn(1, '06:00')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->when(fn () => $enabled() && (bool) config('helpdesktickets.article_drafts.enabled', false));

            // Revisión de calidad por muestreo. Diaria y con muestra pequeña:
            // busca una medida estable, no revisarlo todo.
            $schedule->command('helpdesk:review-quality')
                ->dailyAt('05:30')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->when(fn () => $enabled() && (bool) config('helpdesktickets.quality_review.enabled', false));

            // Observabilidad operativa: snapshot de colas/webhooks/SLA en cache
            // + evaluación de alertas (mail a managers, OFF por defecto).
            $schedule->command('helpdesk:ops-metrics')->everyFiveMinutes()->withoutOverlapping()->onOneServer()->runInBackground()->when($enabled);

            // Informes programados por email (OFF por defecto). La cadencia la
            // decide la frecuencia configurada: semanal (lunes 07:00) o mensual
            // (día 1 a las 07:00). El comando además re-verifica el toggle.
            $reportsEnabled = fn () => helpdesk_tickets_enabled()
                && (bool) config('helpdesktickets.reports.scheduled.enabled', false);
            $reportsFrequency = fn () => (string) config('helpdesktickets.reports.scheduled.frequency', 'weekly');

            $schedule->command('helpdesk:send-scheduled-reports')
                ->weeklyOn(1, '07:00')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->when(fn () => $reportsEnabled() && $reportsFrequency() !== 'monthly');

            $schedule->command('helpdesk:send-scheduled-reports')
                ->monthlyOn(1, '07:00')
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->when(fn () => $reportsEnabled() && $reportsFrequency() === 'monthly');

            $schedule->job(new ProcessRecurringTicketsJob)->everyFifteenMinutes()->withoutOverlapping()->onOneServer()->when($enabled);
            $schedule->job(new CheckSlaBreaches)->everyFifteenMinutes()->withoutOverlapping()->onOneServer()->when($enabled);
            $schedule->job(new SendSlaWarnings)->everyThirtyMinutes()->withoutOverlapping()->onOneServer()->when($enabled);
            $schedule->job(new CleanupOldTickets)->daily()->at('02:00')->onOneServer()->when($enabled);
            // Historial de auditoria de la lista negra (helpdesk_ticket_email_blacklist_hits):
            // crece sin limite, un hit por cada correo bloqueado — purga diaria.
            $schedule->command('helpdesk:prune-blacklist-hits')
                ->daily()->at('02:30')
                ->withoutOverlapping()->onOneServer()->runInBackground()
                ->when($enabled);
            $schedule->job(new EscalateTicketsJob)->everyFifteenMinutes()->withoutOverlapping()->onOneServer()->when($enabled);
            // Barrido de tickets sin asignar (#78): el propio job es inerte si el
            // toggle global de auto-asignación está apagado (default off).
            $schedule->job(new AutoAssignUnassignedTickets)->everyFifteenMinutes()->withoutOverlapping()->onOneServer()->when($enabled);
        });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'config/config.php');

        if (! file_exists($configPath)) {
            return;
        }

        $this->publishes([$configPath => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom($configPath, $this->moduleNameLower);
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $publishPath = resource_path('views/modules/'.$this->moduleNameLower);
        $this->publishes([$sourcePath => $publishPath], ['views', $this->moduleNameLower.'-module-views']);

        $publishedPaths = array_values(array_filter(
            array_map(
                fn ($p) => is_dir($p.'/modules/'.$this->moduleNameLower) ? $p.'/modules/'.$this->moduleNameLower : null,
                config('view.paths'),
            ),
        ));

        $this->loadViewsFrom([...$publishedPaths, $sourcePath], $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        $this->loadManagerRoutes();
        $this->loadTicketTemplatesRoutes();
        $this->loadApiRoutes();
        $this->loadAgentRoutes();
        $this->loadPortalRoutes();
        $this->loadPublicRoutes();

        // Herramientas de desarrollo: nunca en producción (404).
        if (app()->environment(['local', 'testing'])) {
            $this->loadDevRoutes();
        }
    }

    protected function loadDevRoutes(): void
    {
        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/dev')
            ->name('dev.')
            ->group(function () {
                Route::get('email-test', [EmailTestController::class, 'index'])->name('email-test');
                Route::post('email-test/send', [EmailTestController::class, 'send'])->name('email-test.send');
                Route::post('email-test/sync', [EmailTestController::class, 'sync'])->name('email-test.sync');
            });
    }

    protected function loadPublicRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/public.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web'])->group($path);
    }

    protected function loadManagerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/managers.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group($path);
    }

    protected function loadTicketTemplatesRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/ticket-templates.php');

        if (! file_exists($path)) {
            return;
        }

        // Mismo prefijo de URL que managers.php (panel/helpdesk/ticket-templates
        // se mantiene igual), pero con un gate de rol mas amplio: tambien
        // agentes/managers de helpdesk pueden gestionar sus plantillas
        // personales, no solo super-admin/super-settings.
        Route::middleware(['web', 'auth', 'role:helpdesk-agent|helpdesk-manager|manager|super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group($path);
    }

    protected function loadApiRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/api.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/v1/helpdesk')
            ->name('api.v1.helpdesk.')
            ->group($path);
    }

    protected function loadAgentRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/agents.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth', 'role:helpdesk-agent|super-admin|super-settings|manager'])
            ->prefix('panel/helpdesk/agent')
            ->name('agent.helpdesk.')
            ->group($path);
    }

    protected function loadPortalRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/portal.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'throttle:60,1'])
            ->prefix('portal')
            ->name('portal.')
            ->group($path);
    }

    public function provides(): array
    {
        return [];
    }
}
