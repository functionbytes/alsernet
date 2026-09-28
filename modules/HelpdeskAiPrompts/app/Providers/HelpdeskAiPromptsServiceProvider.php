<?php

namespace Modules\HelpdeskAiPrompts\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Listeners\RecordAiAnswerFeedback;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Observers\ConversationItemAiCaseObserver;
use Modules\HelpdeskAiPrompts\Services\PromptLibrary;
use Modules\HelpdeskLivechat\Events\AiAnswerRated;
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
        $this->registerPromptCacheInvalidation();
        $this->registerConversationItemObserver();
        $this->registerFeedbackListener();
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
