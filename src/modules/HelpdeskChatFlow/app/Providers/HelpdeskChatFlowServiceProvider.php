<?php

namespace Modules\HelpdeskChatFlow\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Helpdesk\Services\AI\PromptSanitizer;
use Modules\Helpdesk\Services\AI\SentimentService;
use Modules\Helpdesk\Services\WhatsAppHsmService;
use Modules\HelpdeskChatFlow\Console\Commands\ExpireInactiveSessionsCommand;
use Modules\HelpdeskChatFlow\Console\Commands\LaunchOutboundFlowCommand;
use Modules\HelpdeskChatFlow\Console\Commands\PollAbandonedCartsCommand;
use Modules\HelpdeskChatFlow\Console\Commands\PruneEndedSessionsCommand;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Listeners\InvalidateFlowAnalyticsCache;
use Modules\HelpdeskChatFlow\Listeners\LaunchOutboundFlowOnBusinessEvent;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Observers\ConversationItemObserver;
use Modules\HelpdeskChatFlow\Policies\ChatFlowPolicy;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowDocumentLink;
use Modules\HelpdeskChatFlow\Services\ChatFlowHsmDelivery;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Services\ChatFlowSentiment;
use Modules\HelpdeskChatFlow\Services\ChatFlowVoiceTranscriber;
use Modules\HelpdeskChatFlow\Services\Compliance\ChatflowGdprExportContributor;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskHelpcenter\Services\EmbeddingsService;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskChatFlowServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskChatFlow';

    protected string $moduleNameLower = 'helpdeskchatflow';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerPolicies();
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerListeners();
        $this->registerMenus();
        $this->registerCommands();

        // Seccion 'chatflow_sessions' del export GDPR (derecho de acceso); no
        // atado al toggle de integracion, igual que la cascada de borrado.
        $this->app->tag([ChatflowGdprExportContributor::class], GdprExportContributor::TAG);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireInactiveSessionsCommand::class,
                LaunchOutboundFlowCommand::class,
                PollAbandonedCartsCommand::class,
                PruneEndedSessionsCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('chatflow:expire-sessions --minutes=30')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_chatflow_enabled());
            // Fallback for abandoned-cart outbound when PrestaShop webhooks aren't set up.
            $schedule->command('chatflow:poll-abandoned-carts --minutes=20')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_chatflow_enabled());
            $schedule->command('chatflow:prune-sessions')
                ->daily()
                ->at('03:45')
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_chatflow_enabled());
        });
    }

    public function register(): void
    {
        $this->app->bind(
            CustomerIdentityResolver::class,
            function ($app) {
                $erp = class_exists(ErpContextService::class)
                    ? $app->make(ErpContextService::class)
                    : null;

                $ps = class_exists(PrestashopContextService::class)
                    ? $app->make(PrestashopContextService::class)
                    : null;

                return new CustomerIdentityResolver($erp, $ps);
            }
        );

        $this->app->bind(ChatFlowAiResponder::class, function ($app) {
            $embeddings = class_exists(EmbeddingsService::class)
                ? $app->make(EmbeddingsService::class)
                : null;

            $aiClient = class_exists(AiClient::class)
                ? $app->make(AiClient::class)
                : null;

            $sanitizer = class_exists(PromptSanitizer::class)
                ? $app->make(PromptSanitizer::class)
                : null;

            return new ChatFlowAiResponder($embeddings, $aiClient, $sanitizer);
        });

        $this->app->bind(ChatFlowOrderLookup::class, function ($app) {
            $erp = class_exists(ErpContextService::class)
                ? $app->make(ErpContextService::class)
                : null;

            $ps = class_exists(PrestashopContextService::class)
                ? $app->make(PrestashopContextService::class)
                : null;

            return new ChatFlowOrderLookup($erp, $ps);
        });

        $this->app->bind(ChatFlowAgentService::class, function ($app) {
            $embeddings = class_exists(EmbeddingsService::class)
                ? $app->make(EmbeddingsService::class)
                : null;

            $aiClient = class_exists(AiClient::class)
                ? $app->make(AiClient::class)
                : null;

            $sanitizer = class_exists(PromptSanitizer::class)
                ? $app->make(PromptSanitizer::class)
                : null;

            return new ChatFlowAgentService($app->make(ChatFlowOrderLookup::class), $embeddings, $aiClient, $sanitizer);
        });

        $this->app->bind(ChatFlowSentiment::class, function ($app) {
            $service = class_exists(SentimentService::class)
                ? $app->make(SentimentService::class)
                : null;

            return new ChatFlowSentiment($service);
        });

        $this->app->bind(ChatFlowLocalizer::class, function ($app) {
            $translator = class_exists(CachedTranslator::class)
                ? $app->make(CachedTranslator::class)
                : null;

            return new ChatFlowLocalizer($translator);
        });

        $this->app->bind(ChatFlowVoiceTranscriber::class, function ($app) {
            $aiClient = class_exists(AiClient::class)
                ? $app->make(AiClient::class)
                : null;

            return new ChatFlowVoiceTranscriber($aiClient);
        });

        $this->app->bind(ChatFlowHsmDelivery::class, function ($app) {
            $hsm = class_exists(WhatsAppHsmService::class)
                ? $app->make(WhatsAppHsmService::class)
                : null;

            return new ChatFlowHsmDelivery($hsm);
        });

        $this->app->bind(ChatFlowDocumentLink::class, function ($app) {
            $linker = class_exists(ConversationDocumentLinker::class)
                ? $app->make(ConversationDocumentLinker::class)
                : null;

            return new ChatFlowDocumentLink($linker);
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(ChatFlow::class, ChatFlowPolicy::class);
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
            $this->loadViewsFrom([$sourcePath], 'chatflow');
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
    }

    protected function registerListeners(): void
    {
        // Observer covers all ConversationItem create paths (simulator, webhooks, API).
        // Using an observer instead of ConversationItemCreated/MessageReceived event
        // listeners avoids duplicate triggers: LinkPreviewObserver broadcasts
        // MessageReceived for every item, so a registered listener would double-fire.
        ConversationItem::observe(ConversationItemObserver::class);

        // Invalidate the flow's cached analytics when one of its sessions finishes.
        Event::listen(ChatFlowCompleted::class, InvalidateFlowAnalyticsCache::class);

        // Proactive outbound: launch a chat flow on PrestaShop/ERP business events
        // (only when those modules are installed).
        $businessEvents = [
            PsCartAbandoned::class,
            PsOrderStatusChanged::class,
            ErpOrdersReady::class,
        ];

        foreach ($businessEvents as $event) {
            if (class_exists($event)) {
                Event::listen($event, LaunchOutboundFlowOnBusinessEvent::class);
            }
        }
    }

    protected function registerMenus(): void
    {
        if (! helpdesk_chatflow_enabled()) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Chat Flows',
            'items' => [
                [
                    'label' => 'Chat Flows',
                    'route' => 'chatflow.index',
                    'icon' => 'fas fa-diagram-project',
                    'permission' => 'chatflow.view',
                ],
            ],
        ]);
    }
}
