<?php

namespace Modules\Campaign\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Console\Commands\AlertCheckCommand;
use Modules\Campaign\Console\Commands\ArchiveOldCampaignsCommand;
use Modules\Campaign\Console\Commands\CampaignCompareVariantsCommand;
use Modules\Campaign\Console\Commands\CampaignStatusCommand;
use Modules\Campaign\Console\Commands\CheckLinksCommand;
use Modules\Campaign\Console\Commands\CleanupLogsCommand;
use Modules\Campaign\Console\Commands\DemoCommand;
use Modules\Campaign\Console\Commands\DispatchAutomationJobsCommand;
use Modules\Campaign\Console\Commands\ExecuteScheduledCampaignsCommand;
use Modules\Campaign\Console\Commands\GeneratePartitionDdlCommand;
use Modules\Campaign\Console\Commands\InstallCommand;
use Modules\Campaign\Console\Commands\ListHygieneCommand;
use Modules\Campaign\Console\Commands\QueueAutoscalerCommand;
use Modules\Campaign\Console\Commands\ReEngagementCommand;
use Modules\Campaign\Console\Commands\RssToEmailCommand;
use Modules\Campaign\Console\Commands\SeedEmailTemplatesCommand;
use Modules\Campaign\Console\Commands\SeedPageTemplatesCommand;
use Modules\Campaign\Console\Commands\UpdateEngagementScoresCommand;
use Modules\Campaign\Events\CampaignMessageClicked;
use Modules\Campaign\Events\CampaignMessageOpened;
use Modules\Campaign\Events\CampaignMessageSent;
use Modules\Campaign\Listeners\UpdateCampaignMetrics;
use Modules\Campaign\Listeners\UpdateTrackingLogOnBounce;
use Modules\Campaign\Models\Automation\Automation2;
use Modules\Campaign\Models\Form;
use Modules\Campaign\Models\Funnel\Funnel;
use Modules\Campaign\Models\Template\CustomerEmailTemplate;
use Modules\Campaign\Models\Template\PageTemplate;
use Modules\Campaign\Models\Template\SystemEmailTemplate;
use Modules\Campaign\Policies\PageTemplatePolicy;
use Modules\CampaignSendingServers\Events\BounceDetected;
use Modules\CampaignSendingServers\Events\FeedbackLoopDetected;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class CampaignServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Campaign';

    protected string $moduleNameLower = 'campaign';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/campaign.php'),
            'campaign'
        );
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerSchedules();
        $this->registerCommands();
        $this->registerEventListeners();
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(
            PageTemplate::class,
            PageTemplatePolicy::class,
        );
        Gate::policy(
            SystemEmailTemplate::class,
            PageTemplatePolicy::class,
        );
        Gate::policy(
            CustomerEmailTemplate::class,
            PageTemplatePolicy::class,
        );
        Gate::policy(
            Automation2::class,
            PageTemplatePolicy::class,
        );
        Gate::policy(
            Form::class,
            PageTemplatePolicy::class,
        );
        Gate::policy(
            Funnel::class,
            PageTemplatePolicy::class,
        );
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/campaign.php') => config_path('campaign.php'),
        ], 'config');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    protected function registerTranslations(): void
    {
        $langPath = module_path($this->moduleName, 'lang');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');
        $legacyViewsPath = module_path($this->moduleName, 'views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $paths = $this->getPublishableViewPaths();
        $paths[] = $sourcePath;
        if (is_dir($legacyViewsPath)) {
            // Compat con la carpeta `views/` heredada de Acelle
            $paths[] = $legacyViewsPath;
        }

        $this->loadViewsFrom($paths, $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        // Manager (panel admin)
        $managersPath = module_path($this->moduleName, 'routes/managers.php');
        if (file_exists($managersPath)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/campaign')
                ->group($managersPath);
        }

        // API (Sanctum + throttle 60/min global; endpoints sensibles sobreescriben)
        $apiPath = module_path($this->moduleName, 'routes/api.php');
        if (file_exists($apiPath)) {
            Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
                ->prefix('api/campaign')
                ->name('api.campaign.')
                ->group($apiPath);
        }

        // Público (open pixel, click redirect, unsubscribe — sin auth)
        $webPath = module_path($this->moduleName, 'routes/web.php');
        if (file_exists($webPath)) {
            Route::middleware('web')
                ->prefix('campaign')
                ->group($webPath);
        }
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerMiniItem('campaigns', [
            'icon' => 'fa-duotone fa-thin fa-megaphone',
            'tooltip' => 'Campañas',
            'sidebar_id' => 'campaigns',
            'order' => 30,
        ]);

        NavService::registerSidebar('campaigns', [
            'title' => 'Campañas',
            'permission' => 'campaigns.view.all',
            'items' => [
                ['label' => 'Panel', 'route' => 'manager.dashboard.index', 'icon' => 'fas fa-gauge', 'permission' => 'campaigns.view.all'],
                ['label' => 'Campañas', 'route' => 'manager.campaigns.index', 'icon' => 'fas fa-bullhorn', 'permission' => 'campaigns.view.all'],
                ['label' => 'Listas', 'route' => 'manager.campaigns.maillists.index', 'icon' => 'fas fa-list', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Suscriptores', 'route' => 'manager.campaigns.subscribers.index', 'icon' => 'fas fa-users', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Segmentos', 'route' => 'manager.campaigns.segments.index', 'icon' => 'fas fa-filter', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Plantillas', 'route' => 'manager.campaigns.templates.index', 'icon' => 'fas fa-file-lines', 'permission' => 'campaigns.templates.view'],
                ['label' => 'Plantillas de email', 'route' => 'manager.email_templates.index', 'icon' => 'fas fa-envelope-open-text', 'permission' => 'campaigns.templates.view'],
                ['label' => 'Mis plantillas de email', 'route' => 'manager.my_email_templates.index', 'icon' => 'fas fa-envelope', 'permission' => 'campaigns.templates.view'],
                ['label' => 'Automatizaciones (Flow)', 'route' => 'manager.flow_automations.index', 'icon' => 'fas fa-diagram-project', 'permission' => 'campaigns.automations.view'],
                ['label' => 'Campañas (Pro)', 'route' => 'manager.campaigns_pro.index', 'icon' => 'fas fa-bullhorn', 'permission' => 'campaigns.view.all'],
                ['label' => 'Formularios', 'route' => 'manager.forms.index', 'icon' => 'fas fa-rectangle-list', 'permission' => 'campaigns.maillists.view'],
                ['label' => 'Embudos', 'route' => 'manager.funnels.index', 'icon' => 'fas fa-filter', 'permission' => 'campaigns.view.all'],
                ['label' => 'Plantillas de página', 'route' => 'manager.page_templates.index', 'icon' => 'fas fa-file-code', 'permission' => 'campaigns.templates.view'],
                ['label' => 'Automatizaciones', 'route' => 'manager.campaigns.automations.index', 'icon' => 'fas fa-cogs', 'permission' => 'campaigns.automations.view'],
            ],
        ]);
    }

    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('campaign:execute-scheduled')
                ->name('campaign:execute_scheduled')
                ->everyMinute()
                ->withoutOverlapping(5);

            $schedule->command('campaign:dispatch-automations')
                ->name('campaign:dispatch_automations')
                ->everyFiveMinutes()
                ->withoutOverlapping(5);

            // Cleanup semanal de logs > 180 días.
            $schedule->command('campaign:cleanup --older-than=180')
                ->name('campaign:cleanup_logs')
                ->weekly()
                ->sundays()
                ->at('03:00');

            // Alertas operacionales cada 5 minutos
            $schedule->command('campaign:alert-check')
                ->name('campaign:alert_check')
                ->everyFiveMinutes()
                ->withoutOverlapping(5);

            // Engagement scores cada hora
            $schedule->command('campaign:update-engagement-scores')
                ->name('campaign:update_engagement')
                ->hourly()
                ->withoutOverlapping(30);

            // Re-engagement check diario
            $schedule->command('campaign:re-engagement-check')
                ->name('campaign:reengagement')
                ->dailyAt('02:00')
                ->withoutOverlapping(30);

            // Archivado semanal de campañas antiguas
            $schedule->command('campaign:archive-old --days=365')
                ->name('campaign:archive_old')
                ->weekly()
                ->sundays()
                ->at('04:00')
                ->withoutOverlapping(60);

            // Queue autoscaler check cada minuto
            $schedule->command('campaign:queue-check')
                ->name('campaign:queue_check')
                ->everyMinute()
                ->withoutOverlapping(1);

            // List hygiene mensual (primer domingo a las 05:00)
            $schedule->command('campaign:list-hygiene --days=180')
                ->name('campaign:list_hygiene')
                ->monthlyOn(1, '05:00')
                ->withoutOverlapping(60);
        });
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ExecuteScheduledCampaignsCommand::class,
            DispatchAutomationJobsCommand::class,
            InstallCommand::class,
            CleanupLogsCommand::class,
            DemoCommand::class,
            CampaignStatusCommand::class,
            GeneratePartitionDdlCommand::class,
            AlertCheckCommand::class,
            UpdateEngagementScoresCommand::class,
            ArchiveOldCampaignsCommand::class,
            ReEngagementCommand::class,
            CheckLinksCommand::class,
            QueueAutoscalerCommand::class,
            ListHygieneCommand::class,
            CampaignCompareVariantsCommand::class,
            RssToEmailCommand::class,
            SeedPageTemplatesCommand::class,
            SeedEmailTemplatesCommand::class,
        ]);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(BounceDetected::class, [UpdateTrackingLogOnBounce::class, 'handleBounce']);
        Event::listen(FeedbackLoopDetected::class, [UpdateTrackingLogOnBounce::class, 'handleFeedback']);

        // Métricas materializadas
        Event::listen(CampaignMessageSent::class, [UpdateCampaignMetrics::class, 'handleSent']);
        Event::listen(CampaignMessageOpened::class, [UpdateCampaignMetrics::class, 'handleOpened']);
        Event::listen(CampaignMessageClicked::class, [UpdateCampaignMetrics::class, 'handleClicked']);
        Event::listen(BounceDetected::class, [UpdateCampaignMetrics::class, 'handleBounce']);
        Event::listen(FeedbackLoopDetected::class, [UpdateCampaignMetrics::class, 'handleFeedback']);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') ?? [] as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
