<?php

namespace Modules\HelpdeskTickets\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskTickets\Console\Commands\AutoCloseTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\AutoResponseTicketCommand;
use Modules\HelpdeskTickets\Console\Commands\CleanupTrashedTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\FetchEmailTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\MarkOverdueTicketsCommand;
use Modules\HelpdeskTickets\Console\Commands\SendSlaWarnings as SendSlaWarningsCommand;
use Modules\HelpdeskTickets\Jobs\CheckSlaBreaches;
use Modules\HelpdeskTickets\Jobs\CleanupOldTickets;
use Modules\HelpdeskTickets\Jobs\EscalateTicketsJob;
use Modules\HelpdeskTickets\Jobs\ProcessRecurringTicketsJob;
use Modules\HelpdeskTickets\Jobs\SendSlaWarnings;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Policies\AutomationPolicy;
use Modules\HelpdeskTickets\Policies\MacroPolicy;
use Modules\HelpdeskTickets\Policies\RecurringTicketPolicy;
use Modules\HelpdeskTickets\Policies\SlaPolicyPolicy;
use Modules\HelpdeskTickets\Policies\TicketCannedReplyPolicy;
use Modules\HelpdeskTickets\Policies\TicketCategoryPolicy;
use Modules\HelpdeskTickets\Policies\TicketCommentPolicy;
use Modules\HelpdeskTickets\Policies\TicketGroupPolicy;
use Modules\HelpdeskTickets\Policies\TicketNotePolicy;
use Modules\HelpdeskTickets\Policies\TicketPolicy;
use Modules\HelpdeskTickets\Policies\TicketStatusPolicy;
use Modules\HelpdeskTickets\Policies\TicketTemplatePolicy;
use Modules\HelpdeskTickets\Policies\TicketViewPolicy;
use Modules\HelpdeskTickets\Policies\TimeEntryPolicy;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Services\EscalationService;
use Modules\HelpdeskTickets\Services\SlaService;
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
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerMenus();
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Tickets',
            'items' => [
                [
                    'label' => 'Listado de tickets',
                    'route' => 'manager.helpdesk.tickets.index',
                    'icon' => 'fas fa-ticket',
                    'permission' => 'helpdesk.tickets.view',
                ],
                [
                    'label' => 'Tickets recurrentes',
                    'route' => 'manager.helpdesk.recurring-tickets.index',
                    'icon' => 'fas fa-repeat',
                    'permission' => 'helpdesk.tickets.view',
                ],
                [
                    'label' => 'Plantillas',
                    'route' => 'manager.helpdesk.ticket-templates.index',
                    'icon' => 'fas fa-file-lines',
                    'permission' => 'helpdesk.tickets.manage',
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
            class_exists(FetchEmailTicketsCommand::class) ? FetchEmailTicketsCommand::class : null,
            class_exists(SendSlaWarningsCommand::class) ? SendSlaWarningsCommand::class : null,
        ]));

        if ($commands) {
            $this->commands($commands);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('imap:emailticket')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground();
            $schedule->command('ticket:autoclose')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground();
            $schedule->command('ticket:autooverdue')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground();
            $schedule->command('ticket:autoresponseticket')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground();
            $schedule->command('trashedticket:autodelete')->everyMinute()->withoutOverlapping()->onOneServer()->runInBackground();

            $schedule->job(new ProcessRecurringTicketsJob)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
            $schedule->job(new CheckSlaBreaches)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
            $schedule->job(new SendSlaWarnings)->everyThirtyMinutes()->withoutOverlapping()->onOneServer();
            $schedule->job(new CleanupOldTickets)->daily()->at('02:00')->onOneServer();
            $schedule->job(new EscalateTicketsJob)->hourly()->withoutOverlapping()->onOneServer();
        });
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
        $this->loadApiRoutes();
        $this->loadAgentRoutes();
        $this->loadPortalRoutes();
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

        Route::middleware(['web', 'auth', 'role:helpdesk-agent|super-admin'])
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
