<?php

namespace Modules\HelpdeskAgents\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\HelpdeskAgents\Console\Commands\AdvanceOncallRotations;
use Modules\HelpdeskAgents\Console\Commands\AiUsageReportCommand;
use Modules\HelpdeskAgents\Console\Commands\EncryptAiAgentApiKeys;
use Modules\HelpdeskAgents\Listeners\QueueTicketAiOnTicketCreated;
use Modules\HelpdeskAgents\Listeners\QueueTicketSummaryOnAssigned;
use Modules\HelpdeskAgents\Listeners\QueueTicketSummaryOnEscalation;
use Modules\HelpdeskAgents\Listeners\StartAiAgentSessionOnIncomingMessage;
use Modules\HelpdeskAgents\Mcp\McpToolContext;
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
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\AiUsageRecorder;
use Modules\HelpdeskAgents\Services\EmbeddingService;
use Modules\HelpdeskAgents\Services\KnowledgeRetrievalService;
use Modules\HelpdeskAgents\Services\LlmConnectionTesterService;
use Modules\HelpdeskAgents\Services\McpToolBridge;
use Modules\HelpdeskAgents\Services\PromptSanitizer;
use Modules\HelpdeskAgents\Services\ToolExecutionService;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\TicketHistory;
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
        $this->registerListeners();
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerMenus();
    }

    public function register(): void
    {
        $this->app->singleton(PromptSanitizer::class);
        $this->app->singleton(AiUsageRecorder::class);
        $this->app->singleton(AgentLlmService::class);
        $this->app->singleton(LlmConnectionTesterService::class);
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(KnowledgeRetrievalService::class);
        $this->app->singleton(ToolExecutionService::class);
        $this->app->singleton(McpToolBridge::class);

        // scoped, no singleton: el ambito de las tools MCP se BLOQUEA a un
        // ticket concreto (McpToolContext::scopeToTicket) y un worker de cola
        // reutiliza el contenedor entre jobs — como singleton, el ticket de un
        // job se filtraria al siguiente y las tools responderian con los datos
        // del cliente equivocado. `scoped` lo descarta en cada job/peticion.
        $this->app->scoped(McpToolContext::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AiUsageReportCommand::class,
                AdvanceOncallRotations::class,
                EncryptAiAgentApiKeys::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdesk:agents:advance-oncall')->hourly();
        });
    }

    public function provides(): array
    {
        return [
            PromptSanitizer::class,
            AiUsageRecorder::class,
            AgentLlmService::class,
            LlmConnectionTesterService::class,
            EmbeddingService::class,
            KnowledgeRetrievalService::class,
            ToolExecutionService::class,
            McpToolBridge::class,
            McpToolContext::class,
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

    /**
     * Wire the AI runtime into the Helpdesk conversation flow. The listener is
     * always registered, but it is internally gated by the config('helpdeskagents.enabled')
     * feature flag (OFF by default), so the AI never runs in production unless
     * explicitly enabled per deploy.
     */
    protected function registerListeners(): void
    {
        Event::listen(MessageReceived::class, StartAiAgentSessionOnIncomingMessage::class);

        // Ticket-side AI enrichment (summaries, classification, language
        // routing). Registered here — not in HelpdeskTickets — so the ticket
        // module stays untouched; each queued job re-checks its own feature
        // flag, and a broken LLM can never affect ticket flows.
        if (class_exists(TicketCreated::class)) {
            Event::listen(TicketCreated::class, QueueTicketAiOnTicketCreated::class);
            Event::listen(TicketAssigned::class, QueueTicketSummaryOnAssigned::class);

            // El motor de escalado no emite evento propio: su rastro estable es
            // la fila `escalated` en el historial, así que escuchamos el evento
            // Eloquent de creación de TicketHistory.
            Event::listen(
                'eloquent.created: '.TicketHistory::class,
                QueueTicketSummaryOnEscalation::class
            );
        }
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
        if (! helpdesk_agents_enabled()) {
            return;
        }

        // "AI Agents" (constructor de flujos, con sus pestañas de
        // herramientas/conocimiento/ajustes/etiquetas) se movió por completo
        // a Ajustes: es trabajo de configuración del bot, no una bandeja
        // operativa del día a día — ya no tiene sección propia aquí.
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Agentes',
            'order' => 290,
            'items' => [
                ['label' => 'Turnos y guardias', 'route' => 'settings.helpdesk.schedule.index', 'permission' => 'helpdesk.schedule.view'],
                ['label' => 'AI Agents', 'route' => 'helpdesk.ai.flows.index', 'permission' => 'helpdesk.aiagents.view'],
                // No vivía en ningún menú (solo accesible navegando dentro de
                // la propia sección de AI Agents); ahora es descubrible desde
                // Ajustes, igual que el resto de configuración del módulo.
                ['label' => 'Configuración de IA', 'route' => 'helpdesk.ai.settings', 'permission' => 'helpdesk.aiagents.view'],
            ],
        ]);
    }
}
