<?php

namespace Modules\HelpdeskSocial\Providers;

use App\Console\Commands\AnonymizeSocialUserCommand;
use App\Console\Commands\ExportSocialUserDataCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskSocial\Console\Commands\CheckSlaBreachesCommand;
use Modules\HelpdeskSocial\Console\Commands\ExportSocialCommentsCommand;
use Modules\HelpdeskSocial\Console\Commands\ImportSocialCommentsCommand;
use Modules\HelpdeskSocial\Console\Commands\ResetSocialAccountCommand;
use Modules\HelpdeskSocial\Console\Commands\SocialHealthCheckCommand;
use Modules\HelpdeskSocial\Console\Commands\SyncCompetitorMetricsCommand;
use Modules\HelpdeskSocial\Console\Commands\SyncSocialCommentsCommand;
use Modules\HelpdeskSocial\Console\Commands\SyncSocialMentionsCommand;
use Modules\HelpdeskSocial\Contracts\AutoReplyEngineInterface;
use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialRuleRepositoryInterface;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Contracts\WebhookParserInterface;
use Modules\HelpdeskSocial\Contracts\WebhookVerifierInterface;
use Modules\HelpdeskSocial\Middleware\LogSocialApiCalls;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Models\SocialTemplate;
use Modules\HelpdeskSocial\Policies\SocialAccountPolicy;
use Modules\HelpdeskSocial\Policies\SocialCommentPolicy;
use Modules\HelpdeskSocial\Policies\SocialRulePolicy;
use Modules\HelpdeskSocial\Policies\SocialTemplatePolicy;
use Modules\HelpdeskSocial\Repositories\Eloquent\SocialRuleRepository;
use Modules\HelpdeskSocial\Services\Channels\MetaApiClient;
use Modules\HelpdeskSocial\Services\Channels\MetaChannelProvider;
use Modules\HelpdeskSocial\Services\Channels\MetaWebhookParser;
use Modules\HelpdeskSocial\Services\Channels\MetaWebhookVerifier;
use Modules\HelpdeskSocial\Services\Channels\SocialChannelRegistry;
use Modules\HelpdeskSocial\Services\Classifiers\HybridIntentClassifier;
use Modules\HelpdeskSocial\Services\Classifiers\OpenAiIntentClassifier;
use Modules\HelpdeskSocial\Services\Classifiers\RulesIntentClassifier;
use Modules\HelpdeskSocial\Services\Engines\RuleBasedAutoReplyEngine;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;

class HelpdeskSocialServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'HelpdeskSocial';

    protected string $nameLower = 'helpdesksocial';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );

        $this->registerPolicies();
        $this->registerBindings();
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerMenus();
        $this->registerChannels();
        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $this->commands([
            SyncSocialCommentsCommand::class,
            SyncSocialMentionsCommand::class,
            SyncCompetitorMetricsCommand::class,
            CheckSlaBreachesCommand::class,
            SocialHealthCheckCommand::class,
            ResetSocialAccountCommand::class,
            ExportSocialCommentsCommand::class,
            ImportSocialCommentsCommand::class,
            ExportSocialUserDataCommand::class,
            AnonymizeSocialUserCommand::class,
        ]);
    }

    protected function registerBindings(): void
    {
        // Repositories
        $this->app->singleton(SocialRuleRepositoryInterface::class, SocialRuleRepository::class);

        // Intent Classifier - configurable via config
        $this->app->singleton(IntentClassifierInterface::class, function () {
            $provider = config('helpdesksocial.intent_classification.provider', 'hybrid');

            return match ($provider) {
                'rules' => $this->app->make(RulesIntentClassifier::class),
                'openai' => $this->app->make(OpenAiIntentClassifier::class),
                default => $this->app->make(HybridIntentClassifier::class),
            };
        });

        // API client - default to Meta for now; future platforms will use contextual binding
        $this->app->bind(SocialApiClientInterface::class, MetaApiClient::class);

        // Auto-reply engine
        $this->app->singleton(AutoReplyEngineInterface::class, RuleBasedAutoReplyEngine::class);

        // Webhook parser - resolved via channel registry at runtime
        $this->app->bind(WebhookParserInterface::class, MetaWebhookParser::class);
        $this->app->bind(WebhookVerifierInterface::class, MetaWebhookVerifier::class);

        // Channel registry singleton
        $this->app->singleton(SocialChannelRegistry::class, function () {
            return new SocialChannelRegistry;
        });
    }

    protected function registerChannels(): void
    {
        $registry = $this->app->make(SocialChannelRegistry::class);

        if (config('helpdesksocial.integrations.meta.enabled', true)) {
            $registry->register('meta', MetaChannelProvider::class);
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php'),
        ], 'config');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), $this->nameLower);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);
        Gate::policy(SocialComment::class, SocialCommentPolicy::class);
        Gate::policy(SocialRule::class, SocialRulePolicy::class);
        Gate::policy(SocialTemplate::class, SocialTemplatePolicy::class);
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->name, 'routes/web.php'));

        Route::prefix('api')
            ->middleware('api')
            ->group(module_path($this->name, 'routes/api.php'));

        $settingsRoutes = base_path('modules/HelpdeskSocial/routes/settings.php');

        if (file_exists($settingsRoutes)) {
            Route::middleware(['web', 'auth'])
                ->prefix('panel/settings/helpdesk/social')
                ->name('settings.helpdesk.social.')
                ->group($settingsRoutes);
        }
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('log.social.api', LogSocialApiCalls::class);
    }

    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('helpdesk-social', [
            'title' => 'Social',
            'items' => [
                ['label' => 'Cuentas conectadas', 'route' => 'helpdesksocial.accounts.index', 'permission' => 'helpdesksocial.view'],
                ['label' => 'Bandeja social', 'route' => 'helpdesksocial.inbox.index', 'permission' => 'helpdesksocial.view'],
                ['label' => 'Conversaciones', 'route' => 'helpdesksocial.conversations.index', 'permission' => 'helpdesksocial.view'],
                ['label' => 'Menciones', 'route' => 'helpdesksocial.mentions.index', 'permission' => 'helpdesksocial.view'],
                ['label' => 'Reglas automáticas', 'route' => 'helpdesksocial.rules.index', 'permission' => 'helpdesksocial.manage-rules'],
                ['label' => 'Asignación inteligente', 'route' => 'helpdesksocial.assignment-rules.index', 'permission' => 'helpdesksocial.manage-rules'],
                ['label' => 'Plantillas', 'route' => 'helpdesksocial.templates.index', 'permission' => 'helpdesksocial.manage-templates'],
                ['label' => 'Tags', 'route' => 'helpdesksocial.tags.index', 'permission' => 'helpdesksocial.manage-rules'],
                ['label' => 'SLA', 'route' => 'helpdesksocial.sla-policies.index', 'permission' => 'helpdesksocial.manage-rules'],
                ['label' => 'Aprobaciones', 'route' => 'helpdesksocial.approval-requests.index', 'permission' => 'helpdesksocial.view'],
                ['label' => 'Competidores', 'route' => 'helpdesksocial.competitors.index', 'permission' => 'helpdesksocial.view-analytics'],
                ['label' => 'Analítica', 'route' => 'helpdesksocial.analytics.index', 'permission' => 'helpdesksocial.view-analytics'],
                ['label' => 'Rendimiento agentes', 'route' => 'helpdesksocial.analytics.agents', 'permission' => 'helpdesksocial.view-analytics'],
            ],
        ]);

        NavService::registerSidebar('helpdesk-social-settings', [
            'title' => 'Configuración Social',
            'items' => [
                ['label' => 'General', 'route' => 'settings.helpdesk.social.index', 'permission' => 'helpdesksocial.manage-rules'],
            ],
        ]);
    }
}
