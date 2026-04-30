<?php

namespace Modules\HelpdeskAgents\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Modules\HelpdeskAgents\Models\OncallRotation;
use Modules\HelpdeskAgents\Policies\AgentShiftPolicy;
use Modules\HelpdeskAgents\Policies\AiAgentFlowPolicy;
use Modules\HelpdeskAgents\Policies\AiAgentPolicy;
use Modules\HelpdeskAgents\Policies\OncallRotationPolicy;
use Modules\HelpdeskAgents\Services\PromptSanitizer;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskAgentsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskAgents';

    protected string $moduleNameLower = 'helpdeskagents';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('HelpdeskAgents', 'database/migrations'));
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerRateLimiters();
        $this->registerMenus();
    }

    public function register(): void
    {
        $this->app->singleton(PromptSanitizer::class);
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        if (class_exists(AiAgent::class) && class_exists(AiAgentPolicy::class)) {
            Gate::policy(AiAgent::class, AiAgentPolicy::class);
        }

        if (class_exists(AiAgentFlow::class) && class_exists(AiAgentFlowPolicy::class)) {
            Gate::policy(AiAgentFlow::class, AiAgentFlowPolicy::class);
        }

        if (class_exists(AgentShift::class) && class_exists(AgentShiftPolicy::class)) {
            Gate::policy(AgentShift::class, AgentShiftPolicy::class);
        }

        if (class_exists(OncallRotation::class) && class_exists(OncallRotationPolicy::class)) {
            Gate::policy(OncallRotation::class, OncallRotationPolicy::class);
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

    protected function registerRoutes(): void
    {
        $managersRoutes = module_path($this->moduleName, 'routes/managers.php');

        if (file_exists($managersRoutes)) {
            Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
                ->prefix('panel/helpdesk')
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

    /**
     * Rate limiters for LLM calls. Migrated here from the HelpdeskServiceProvider
     * so the agents module owns its throttling policy.
     */
    protected function registerRateLimiters(): void
    {
        $perMinute = (int) config('helpdeskagents.llm_rate_limits.per_user_per_minute', 10);
        $perFiveMin = (int) config('helpdeskagents.llm_rate_limits.per_session_per_5min', 30);
        $perDay = (int) config('helpdeskagents.llm_rate_limits.per_user_per_day', 1000);

        RateLimiter::for('llm-per-user', fn (Request $request) => Limit::perMinute($perMinute)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('llm-per-session', fn (Request $request) => Limit::perMinutes(5, $perFiveMin)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('llm-per-user-per-day', fn (Request $request) => Limit::perDay($perDay)
            ->by($request->user()?->id ?: $request->ip()));
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Agentes IA',
            'items' => [
                [
                    'label' => 'AI Agents',
                    'route' => 'manager.helpdesk.ai.flows.index',
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

    public function provides(): array
    {
        return [PromptSanitizer::class];
    }
}
