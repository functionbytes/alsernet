<?php

namespace Modules\HelpdeskAgents\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;
use Modules\HelpdeskAgents\Models\OncallRotation;
use Modules\HelpdeskAgents\Observers\AiAgentKnowledgeBaseObserver;
use Modules\HelpdeskAgents\Observers\AiAgentObserver;
use Modules\HelpdeskAgents\Policies\AgentShiftPolicy;
use Modules\HelpdeskAgents\Policies\AgentVacationPolicy;
use Modules\HelpdeskAgents\Policies\AiAgentFlowPolicy;
use Modules\HelpdeskAgents\Policies\AiAgentPolicy;
use Modules\HelpdeskAgents\Policies\OncallRotationPolicy;
use Modules\HelpdeskAgents\Services\EmbeddingService;
use Modules\HelpdeskAgents\Services\KnowledgeRetrievalService;
use Modules\HelpdeskAgents\Services\LlmConnectionTesterService;
use Modules\HelpdeskAgents\Services\PromptSanitizer;
use Modules\HelpdeskAgents\Services\ToolExecutionService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskAgentsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskAgents';

    protected string $moduleNameLower = 'helpdeskagents';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerMenus();
    }

    public function register(): void
    {
        $this->app->singleton(PromptSanitizer::class);
        $this->app->singleton(LlmConnectionTesterService::class);
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(KnowledgeRetrievalService::class);
        $this->app->singleton(ToolExecutionService::class);
    }

    public function provides(): array
    {
        return [
            PromptSanitizer::class,
            LlmConnectionTesterService::class,
            EmbeddingService::class,
            KnowledgeRetrievalService::class,
            ToolExecutionService::class,
        ];
    }

    protected function registerPolicies(): void
    {
        Gate::policy(AiAgent::class, AiAgentPolicy::class);
        Gate::policy(AiAgentFlow::class, AiAgentFlowPolicy::class);
        Gate::policy(AgentShift::class, AgentShiftPolicy::class);
        Gate::policy(AgentVacation::class, AgentVacationPolicy::class);
        Gate::policy(OncallRotation::class, OncallRotationPolicy::class);
    }

    protected function registerObservers(): void
    {
        AiAgent::observe(AiAgentObserver::class);
        AiAgentKnowledgeBase::observe(AiAgentKnowledgeBaseObserver::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'config/config.php');

        if (file_exists($configPath)) {
            $this->publishes([$configPath => config_path($this->moduleNameLower.'.php')], 'config');
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');

        if (is_dir($sourcePath)) {
            $this->loadViewsFrom([$sourcePath], $this->moduleNameLower);
        }
    }

    protected function registerTranslations(): void
    {
        $langPath = module_path($this->moduleName, 'lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    protected function registerRoutes(): void
    {
        $managersRoutes = module_path($this->moduleName, 'routes/managers.php');

        if (file_exists($managersRoutes)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/helpdesk')
                ->name('helpdesk.ai.')
                ->group($managersRoutes);
        }

        $settingsRoutes = module_path($this->moduleName, 'routes/settings.php');

        if (file_exists($settingsRoutes)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/settings/helpdesk')
                ->name('settings.helpdesk.')
                ->group($settingsRoutes);
        }
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Agentes IA',
            'items' => [
                [
                    'label' => 'AI Agents',
                    'route' => 'helpdesk.ai.flows.index',
                    'icon' => 'fas fa-robot',
                    'permission' => 'helpdesk.aiagents.view',
                ],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Agentes',
            'items' => [
                ['label' => 'Turnos y guardias', 'route' => 'settings.helpdesk.schedule.index', 'permission' => 'helpdesk.schedule.view'],
            ],
        ]);
    }
}
