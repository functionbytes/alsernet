<?php

namespace Modules\HelpdeskAiPrompts\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Listeners\RecordAiAnswerFeedback;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Observers\ConversationItemAiCaseObserver;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionRegistry;
use Modules\HelpdeskAiPrompts\Services\PromptLibrary;
use Modules\HelpdeskLivechat\Events\AiAnswerRated;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskAiPromptsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskAiPrompts';

    protected string $moduleNameLower = 'helpdeskaiprompts';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        $this->registerPromptCacheInvalidation();
        $this->registerActionCacheInvalidation();
        $this->registerConversationItemObserver();
        $this->registerFeedbackListener();
        $this->registerRoutes();
        $this->registerMenus();
    }

    protected function registerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/web.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth', 'can:helpdesk.ai-prompts.view'])
            ->prefix('panel/helpdesk/ai-prompts')
            ->name('helpdesk-ai-prompts.')
            ->group($path);
    }

    /**
     * Entrada en el sidebar principal "Helpdesk" (sidebar_id registrado por
     * el propio módulo Helpdesk), junto a Bandeja/Reportes/Herramientas.
     */
    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Asistente IA',
            'items' => [
                [
                    'label' => 'Biblioteca de prompts',
                    'route' => 'helpdesk-ai-prompts.index',
                    'icon' => 'fas fa-wand-magic-sparkles',
                    'permission' => 'helpdesk.ai-prompts.view',
                ],
            ],
        ]);
    }

    public function register(): void
    {
        //
    }

    /**
     * PromptLibrary caches blocks and effective cases for 5 min; any
     * create/update/delete must invalidate them, or the panel would take up
     * to 5 min to reflect the change (same pattern as WidgetConversationService
     * in HelpdeskLivechat).
     */
    protected function registerPromptCacheInvalidation(): void
    {
        $forgetBlocks = function (): void {
            Cache::forget(PromptLibrary::CACHE_KEY_BASE_BLOCKS);
            Cache::forget(PromptLibrary::CACHE_KEY_KNOWLEDGE_BLOCKS);
        };
        AiPromptBlock::saved($forgetBlocks);
        AiPromptBlock::deleted($forgetBlocks);

        // Sin valor de retorno: un listener de evento de modelo que devuelve
        // false (Cache::forget sin clave) corta los siguientes listeners, y se
        // perdía el guardado de versiones del caso.
        $forgetCases = function (): void {
            Cache::forget(PromptLibrary::CACHE_KEY_CASES);
        };
        AiPromptCase::saved($forgetCases);
        AiPromptCase::deleted($forgetCases);
    }

    /**
     * ActionRegistry cachea 5 min las acciones activas y los overrides de las
     * integradas; guardar o borrar una acción debe invalidarlas. Closure void
     * a propósito (ver comentario de arriba: un `false` cortaría el guardado
     * de versiones).
     */
    protected function registerActionCacheInvalidation(): void
    {
        $forget = function (): void {
            ActionRegistry::forget();
        };
        AiAction::saved($forget);
        AiAction::deleted($forget);
    }

    /**
     * Links every ConversationItem created by the AI agent (metadata.ai_agent)
     * to its AiPromptRun (via the flow session's trace_id) so metrics can be
     * measured per case, and tags the item with the resolved case.
     */
    protected function registerConversationItemObserver(): void
    {
        ConversationItem::observe(ConversationItemAiCaseObserver::class);
    }

    /**
     * HelpdeskLivechat fires AiAnswerRated when the customer rates a bot
     * answer (👍/👎) from the widget.
     */
    protected function registerFeedbackListener(): void
    {
        if (class_exists(AiAnswerRated::class)) {
            Event::listen(AiAnswerRated::class, RecordAiAnswerFeedback::class);
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

    public function provides(): array
    {
        return [];
    }
}
