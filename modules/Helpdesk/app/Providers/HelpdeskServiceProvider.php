<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Console\Commands\AutoCloseTicketsCommand;
use Modules\Helpdesk\Console\Commands\AutoResponseTicketCommand;
use Modules\Helpdesk\Console\Commands\CleanupTrashedTicketsCommand;
use Modules\Helpdesk\Console\Commands\FetchEmailTicketsCommand;
use Modules\Helpdesk\Console\Commands\MarkOverdueTicketsCommand;
use Modules\Helpdesk\Http\ViewComposers\NavigationComposer;
use Modules\Helpdesk\Jobs\CheckSlaBreaches;
use Modules\Helpdesk\Jobs\CleanupOldTickets;
use Modules\Helpdesk\Jobs\EscalateTicketsJob;
use Modules\Helpdesk\Jobs\ProcessRecurringTicketsJob;
use Modules\Helpdesk\Jobs\SendSlaWarnings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\RecurringTicket;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketComment;
use Modules\Helpdesk\Models\TicketNote;
use Modules\Helpdesk\Models\TicketTemplate;
use Modules\Helpdesk\Models\TicketTimeEntry;
use Modules\Helpdesk\Policies\ConversationPolicy;
use Modules\Helpdesk\Policies\CustomerPolicy;
use Modules\Helpdesk\Policies\HelpCenterArticlePolicy;
use Modules\Helpdesk\Policies\RecurringTicketPolicy;
use Modules\Helpdesk\Policies\TicketCommentPolicy;
use Modules\Helpdesk\Policies\TicketNotePolicy;
use Modules\Helpdesk\Policies\TicketPolicy;
use Modules\Helpdesk\Policies\TicketTemplatePolicy;
use Modules\Helpdesk\Policies\TimeEntryPolicy;
use Modules\Helpdesk\Services\AiAgentFlowEngine;
use Modules\Helpdesk\Services\AssignmentService;
use Modules\Helpdesk\Services\CannedReplyService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CustomerStatsService;
use Modules\Helpdesk\Services\EscalationService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\NotificationService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\PromptSanitizer;
use Modules\Helpdesk\Services\SlaService;
use Modules\Helpdesk\Services\TicketService;
use Modules\Helpdesk\Services\TicketUpdateService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HelpdeskServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Helpdesk';

    protected string $nameLower = 'helpdesk';

    public function boot(): void
    {
        if (Module::find('Helpdesk')?->isDisabled()) {
            return;
        }

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register services as singletons
        $this->app->singleton(TicketService::class);
        $this->app->singleton(TicketUpdateService::class);
        $this->app->singleton(ConversationTagService::class);
        $this->app->singleton(CustomerStatsService::class);
        $this->app->singleton(AssignmentService::class);
        $this->app->singleton(SlaService::class);
        $this->app->singleton(CannedReplyService::class);
        $this->app->singleton(NotificationService::class);

        // Social media integration services
        $this->app->singleton(WhatsAppBusinessService::class);
        $this->app->singleton(FacebookMessengerService::class);
        $this->app->singleton(InstagramService::class);
        $this->app->singleton(OutboundMessageService::class);

        $this->app->singleton(EscalationService::class);
        $this->app->singleton(PromptSanitizer::class);
        $this->app->singleton(AiAgentFlowEngine::class);

        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Ticket::class => TicketPolicy::class,
            Conversation::class => ConversationPolicy::class,
            Customer::class => CustomerPolicy::class,
            HelpCenterArticle::class => HelpCenterArticlePolicy::class,
            TicketNote::class => TicketNotePolicy::class,
            TicketComment::class => TicketCommentPolicy::class,
            TicketTimeEntry::class => TimeEntryPolicy::class,
            RecurringTicket::class => RecurringTicketPolicy::class,
            TicketTemplate::class => TicketTemplatePolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model)) {
                Gate::policy($model, $policy);
            }
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoCloseTicketsCommand::class,
                MarkOverdueTicketsCommand::class,
                AutoResponseTicketCommand::class,
                CleanupTrashedTicketsCommand::class,
                FetchEmailTicketsCommand::class,
            ]);
        }
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Email to ticket processing
            $schedule->command('imap:emailticket')->everyMinute();

            // Auto-close tickets
            $schedule->command('ticket:autoclose')->everyMinute();

            // Auto-overdue ticket detection
            $schedule->command('ticket:autooverdue')->everyMinute();

            // Auto-response for tickets
            $schedule->command('ticket:autoresponseticket')->everyMinute();

            // Clean up trashed tickets
            $schedule->command('trashedticket:autodelete')->everyMinute();

            // Recurring ticket schedules
            $schedule->job(new ProcessRecurringTicketsJob)
                ->everyFifteenMinutes()
                ->withoutOverlapping();

            // SLA breach checking
            $schedule->job(new CheckSlaBreaches)
                ->everyFifteenMinutes()
                ->withoutOverlapping();

            // SLA warning notifications
            $schedule->job(new SendSlaWarnings)
                ->everyThirtyMinutes();

            // Clean up old closed tickets
            $schedule->job(new CleanupOldTickets)
                ->daily()
                ->at('02:00');

            // Priority escalation for overdue tickets
            $schedule->job(new EscalateTicketsJob)
                ->hourly()
                ->withoutOverlapping();
        });
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

    public function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

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
        view()->composer(
            'theme.components.nav',
            NavigationComposer::class
        );
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
