<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Broadcasting\ResilientBroadcaster;
use Modules\Helpdesk\Console\Commands\ChannelMetricsCommand;
use Modules\Helpdesk\Console\Commands\CheckSlaBreaches;
use Modules\Helpdesk\Console\Commands\CleanupAgentPresence;
use Modules\Helpdesk\Console\Commands\FetchEmailTicketsCommand;
use Modules\Helpdesk\Console\Commands\MetaTokenStatusCommand;
use Modules\Helpdesk\Console\Commands\ProcessScheduledBroadcasts;
use Modules\Helpdesk\Console\Commands\PurgeOldGdprDeletes;
use Modules\Helpdesk\Console\Commands\PurgeSimulatorConversationsCommand;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Listeners\Automation\RunAutomationsOnEventListener;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\CustomAttribute;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Observers\ConversationItemLinkPreviewObserver;
use Modules\Helpdesk\Observers\ConversationObserver;
use Modules\Helpdesk\Policies\CannedReplyPolicy;
use Modules\Helpdesk\Policies\ConversationPolicy;
use Modules\Helpdesk\Policies\ConversationStatusPolicy;
use Modules\Helpdesk\Policies\ConversationTagPolicy;
use Modules\Helpdesk\Policies\ConversationViewPolicy;
use Modules\Helpdesk\Policies\CustomAttributePolicy;
use Modules\Helpdesk\Policies\CustomerPolicy;
use Modules\Helpdesk\Policies\GroupPolicy;
use Modules\Helpdesk\Policies\WebhookPolicy;
use Modules\Helpdesk\Services\Automation\Actions\AddLabelAction;
use Modules\Helpdesk\Services\Automation\Actions\AddPrivateNoteAction;
use Modules\Helpdesk\Services\Automation\Actions\AssignAgentAction;
use Modules\Helpdesk\Services\Automation\Actions\AssignTeamAction;
use Modules\Helpdesk\Services\Automation\Actions\ChangePriorityAction;
use Modules\Helpdesk\Services\Automation\Actions\ChangeStatusAction;
use Modules\Helpdesk\Services\Automation\Actions\MuteConversationAction;
use Modules\Helpdesk\Services\Automation\Actions\RemoveLabelAction;
use Modules\Helpdesk\Services\Automation\Actions\SendMessageAction;
use Modules\Helpdesk\Services\Automation\Actions\SendWebhookAction;
use Modules\Helpdesk\Services\Automation\Actions\SnoozeConversationAction;
use Modules\Helpdesk\Services\Automation\AutomationActionRegistry;
use Modules\Helpdesk\Services\CannedReplyService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CustomerStatsService;
use Modules\Helpdesk\Services\EmailInboundService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\NullTicketService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\Public\SimulatorOutboundMessageService;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Modules\Helpdesk\Jobs\SyncWhatsAppTemplatesJob;
use Modules\Theme\Services\NavService;
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

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerHelpdeskSidebar();
        $this->registerSettingsSidebar();
        $this->registerChannelMorphMap();
        $this->registerObservers();
        $this->registerRateLimiters();
        $this->registerAutomationListeners();
        $this->registerSimulatorOutboundGuard();
    }

    /**
     * Envuelve el driver de websockets para que una caída de Reverb no tumbe la
     * petición que emite el evento (ver ResilientBroadcaster). Se registra como
     * driver propio y se apunta la conexión 'reverb' a él, de forma que cubre
     * los ~35 puntos que llaman a broadcast() sin tocarlos uno a uno — y sin
     * romper el encadenado ->toOthers(), que actúa sobre el PendingBroadcast.
     */
    private function registerResilientBroadcaster(): void
    {
        // Diferido a booting(): en register() el contenedor todavía no tiene el
        // binding del BroadcastManager. Los callbacks de booting corren antes que
        // el boot() de cualquier provider, así que llegamos antes de que
        // HelpdeskLivechat resuelva la conexión al declarar sus canales — y sin
        // purgar el driver, que descartaría esas autorizaciones.
        $this->app->booting(function ($app) {
            $app->make(BroadcastManager::class)->extend('reverb', function ($app, array $config) {
                $manager = $app->make(BroadcastManager::class);

                return new ResilientBroadcaster(
                    new PusherBroadcaster($manager->pusher($config))
                );
            });
        });
    }

    /**
     * When the public simulator is enabled, swap OutboundMessageService for a
     * subclass that blocks real channel API calls on simulated conversations.
     * Runs in boot() because it depends on the merged module config; never
     * registered in production (the flag defaults to false). Nothing resolves
     * OutboundMessageService before this point, so the rebind always wins.
     */
    protected function registerSimulatorOutboundGuard(): void
    {
        if (! config('helpdesk.simulator_public_enabled')) {
            return;
        }

        $this->app->singleton(
            OutboundMessageService::class,
            SimulatorOutboundMessageService::class,
        );
    }

    /**
     * Register automation rule event listeners.
     * Rules with matching event_name + conditions trigger configured actions.
     */
    protected function registerAutomationListeners(): void
    {
        $listener = RunAutomationsOnEventListener::class;

        \Event::listen(ConversationCreated::class, [$listener, 'handleConversationCreated']);
        \Event::listen(ConversationUpdated::class, [$listener, 'handleConversationUpdated']);
        \Event::listen(ConversationClosed::class, [$listener, 'handleConversationClosed']);
        \Event::listen(MessageReceived::class, [$listener, 'handleMessageReceived']);

        // TranslateIncomingMessage listener moved to HelpdeskTranslate module.
    }

    /**
     * Register named rate limiters so endpoints can opt out of the global
     * throttle (which is constantly being burned by background pollers).
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('helpdesk-msg-actions', function ($request) {
            return Limit::perMinute(120)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('helpdesk-read', fn ($request) => Limit::perMinute(120)
            ->by(optional($request->user())->id ?: $request->ip()));

        RateLimiter::for('helpdesk-write', fn ($request) => Limit::perMinute(60)
            ->by(optional($request->user())->id ?: $request->ip()));

        RateLimiter::for('helpdesk-ai', fn ($request) => Limit::perMinute(20)
            ->by(optional($request->user())->id ?: $request->ip()));

        RateLimiter::for('helpdesk-export', fn ($request) => Limit::perMinute(5)
            ->by(optional($request->user())->id ?: $request->ip()));
    }

    /**
     * Register Eloquent model observers for cross-cutting concerns
     * (link previews, etc.).
     */
    protected function registerObservers(): void
    {
        ConversationItem::observe(ConversationItemLinkPreviewObserver::class);
        Conversation::observe(ConversationObserver::class);
    }

    /**
     * Register the morph alias map so Inbox->channel() resolves to the correct
     * channel implementation (Web, Email, Facebook, Instagram, Sms, Whatsapp, Api)
     * based on the helpdesk_inboxes.channel_type slug.
     */
    protected function registerChannelMorphMap(): void
    {
        // Use morphMap (soft) rather than enforceMorphMap so we only ADD aliases
        // for the Helpdesk channel implementations without forcing the rest of
        // the application's models (Chat, Page, etc.) to also be in the map.
        Relation::morphMap(
            Inbox::CHANNEL_TYPE_MAP,
            true
        );
    }

    /**
     * Items del módulo Helpdesk en el sidebar principal (sección "Centro de ayuda").
     */
    protected function registerHelpdeskSidebar(): void
    {
        NavService::registerMiniItem('helpdesk', [
            'icon' => 'fa-duotone fa-thin fa-ticket',
            'tooltip' => 'Helpdesk',
            'sidebar_id' => 'helpdesk',
            'order' => 70,
        ]);

        // HelpCenter sidebar is registered by HelpdeskHelpcenterServiceProvider
    }

    /**
     * Items del módulo Helpdesk en el sidebar de Configuración.
     */
    protected function registerSettingsSidebar(): void
    {
        // Sección 1: General
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — General',
            'items' => [
                ['label' => 'Funcionalidades', 'route' => 'settings.helpdesk.features.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Notificaciones', 'route' => 'settings.helpdesk.notifications', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Panel de administración', 'route' => 'settings.helpdesk.business.features', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Horarios de atención', 'route' => 'settings.helpdesk.business.hours', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Fuera de horario', 'route' => 'settings.helpdesk.business.off-hours', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Bienvenida', 'route' => 'settings.helpdesk.business.greeting', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Despedida', 'route' => 'settings.helpdesk.business.farewell', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Subida de archivos', 'route' => 'settings.helpdesk.uploading', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Integraciones', 'route' => 'settings.helpdesk.integrations.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        // Sección 2: Canales
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Canales',
            'items' => [
                ['label' => 'Bandejas (multi-canal)', 'route' => 'settings.helpdesk.inboxes.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Email', 'route' => 'settings.helpdesk.email', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Cuentas de email', 'route' => 'settings.helpdesk.email-accounts.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'LiveChat', 'route' => 'settings.helpdesk-livechat.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Integraciones sociales', 'route' => 'settings.helpdesk.social-integrations.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Plantillas WhatsApp', 'route' => 'settings.helpdesk.whatsapp-templates.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Consumo WhatsApp', 'route' => 'settings.helpdesk.whatsapp-usage.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Slack', 'route' => 'settings.helpdesk.slack-integrations.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        // Sección 3: Equipo
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Equipo',
            'items' => [
                ['label' => 'Miembros', 'route' => 'settings.helpdesk.team.members', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Grupos', 'route' => 'settings.helpdesk.team.groups', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Configuración de agentes', 'route' => 'settings.helpdesk.agent-settings.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Competencias', 'route' => 'settings.helpdesk.skills.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        // Sección 4: Contenido
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Contenido',
            'items' => [
                ['label' => 'Respuestas predefinidas', 'route' => 'settings.helpdesk.canned-replies.index', 'permission' => 'helpdesk.canned-replies.view'],
                ['label' => 'Macros', 'route' => 'settings.helpdesk.macros.index', 'permission' => 'helpdesk.macros.view'],
                ['label' => 'Vistas personalizadas', 'route' => 'settings.helpdesk.views.index', 'permission' => 'helpdesk.views.view'],
                ['label' => 'Etiquetas', 'route' => 'settings.helpdesk.tags.index', 'permission' => 'helpdesk.tags.view'],
                ['label' => 'Estados de conversación', 'route' => 'settings.helpdesk.statuses.index', 'permission' => 'helpdesk.statuses.view'],
                ['label' => 'Encuestas', 'route' => 'settings.helpdesk.surveys.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Banners', 'route' => 'settings.helpdesk.banners.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Marcas', 'route' => 'settings.helpdesk.brands.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        // Sección 5: Automatización
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Automatización',
            'items' => [
                ['label' => 'Reglas de automatización', 'route' => 'settings.helpdesk.rules.index', 'permission' => 'helpdesk.automation-rules.view'],
                ['label' => 'Reglas de enrutamiento', 'route' => 'settings.helpdesk.routing-rules.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Workflows', 'route' => 'settings.helpdesk.workflows.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Políticas SLA', 'route' => 'settings.helpdesk.sla-policies.index', 'permission' => 'helpdesk.sla-policies.view'],
                ['label' => 'Campañas drip', 'route' => 'settings.helpdesk.drip-campaigns.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        // Sección 6: Sistema
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — Sistema',
            'items' => [
                ['label' => 'Configuración de tickets', 'route' => 'manager.helpdesk.settings.tickets.general', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Atributos personalizados', 'route' => 'settings.helpdesk.attributes.index', 'permission' => 'helpdesk.attributes.view'],
                ['label' => 'Campos personalizados', 'route' => 'settings.helpdesk.custom-fields.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Empresas', 'route' => 'settings.helpdesk.companies.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Página de estado', 'route' => 'settings.helpdesk.status.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Webhooks', 'route' => 'settings.helpdesk.webhooks.index', 'permission' => 'helpdesk.webhooks.view'],
                ['label' => 'Tokens API', 'route' => 'settings.helpdesk.profile.tokens.index', 'permission' => 'helpdesk.view'],
                ['label' => 'Registro de auditoría', 'route' => 'settings.helpdesk.audits.index', 'permission' => 'helpdesk.settings.view'],
            ],
        ]);

        NavService::registerSidebar('helpdesk', [
            'title' => 'Bandeja',
            'items' => [
                ['label' => 'Conversaciones', 'route' => 'manager.helpdesk.conversations.index', 'icon' => 'fas fa-inbox', 'permission' => 'helpdesk.conversations.view'],
            ],
        ]);

        NavService::registerSidebar('helpdesk', [
            'title' => 'Reportes',
            'items' => [
                ['label' => 'Satisfacción (CSAT)', 'route' => 'manager.helpdesk.reports.csat', 'permission' => 'helpdesk.reports.view'],
                ['label' => 'Clientes en riesgo', 'route' => 'manager.helpdesk.reports.at-risk', 'icon' => 'fas fa-heart-crack', 'permission' => 'helpdesk.reports.view'],
                ['label' => 'Incumplimientos SLA', 'route' => 'manager.helpdesk.reports.sla-breaches', 'icon' => 'fas fa-gauge-high', 'permission' => 'helpdesk.reports.view'],
            ],
        ]);

        NavService::registerSidebar('helpdesk', [
            'title' => 'Herramientas',
            'items' => [
                ['label' => 'Búsqueda global', 'route' => 'manager.helpdesk.search', 'icon' => 'fas fa-search', 'permission' => 'helpdesk.view'],
            ],
        ]);
    }

    public function register(): void
    {
        require_once __DIR__.'/../Helpers/helpers.php';

        // En register(), no en boot(): el manager cachea el driver la primera vez
        // que se resuelve, y HelpdeskLivechat lo resuelve en su boot() al declarar
        // los canales. Todos los register() corren antes que cualquier boot(), así
        // que aquí el decorador siempre gana sin tener que purgar el driver (lo
        // que descartaría las autorizaciones de canal ya registradas).
        $this->registerResilientBroadcaster();

        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register services as singletons
        $this->app->singleton(LiquidRenderer::class);
        $this->app->singleton(ConversationTagService::class);
        $this->app->singleton(CustomerStatsService::class);
        $this->app->singleton(CannedReplyService::class);

        // Social media integration services
        $this->app->singleton(WhatsAppBusinessService::class);
        $this->app->singleton(FacebookMessengerService::class);
        $this->app->singleton(InstagramService::class);
        $this->app->singleton(OutboundMessageService::class);

        // Email inbound service
        $this->app->singleton(EmailInboundService::class);

        // Automation action registry — singleton populated with every available
        // action so rules and macros can resolve and execute them at runtime.
        $this->app->singleton(AutomationActionRegistry::class, function (): AutomationActionRegistry {
            $registry = new AutomationActionRegistry;

            $actions = [
                AddLabelAction::class,
                AddPrivateNoteAction::class,
                AssignAgentAction::class,
                AssignTeamAction::class,
                ChangePriorityAction::class,
                ChangeStatusAction::class,
                MuteConversationAction::class,
                RemoveLabelAction::class,
                SendMessageAction::class,
                SendWebhookAction::class,
                SnoozeConversationAction::class,
            ];

            foreach ($actions as $action) {
                $registry->register($action::actionType(), $action);
            }

            return $registry;
        });

        // HelpdeskTickets bridge — concrete impl when integration is enabled,
        // null fallback otherwise. Helpdesk code resolves this contract only.
        $this->app->singleton(TicketServiceContract::class, function () {
            if (function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled()) {
                $concrete = '\\Modules\\HelpdeskTickets\\Services\\HelpdeskTicketBridgeService';

                if (class_exists($concrete)) {
                    return $this->app->make($concrete);
                }
            }

            return new NullTicketService;
        });

        $this->commands([
            FetchEmailTicketsCommand::class,
            CheckSlaBreaches::class,
            CleanupAgentPresence::class,
            ProcessScheduledBroadcasts::class,
            PurgeSimulatorConversationsCommand::class,
            PurgeOldGdprDeletes::class,
            MetaTokenStatusCommand::class,
            ChannelMetricsCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdesk:check-sla')->everyFiveMinutes();
            $schedule->command('helpdesk:process-broadcasts')->everyMinute();

            // Mark agents whose heartbeat lapsed (Redis presence TTL is 90s) as
            // offline so presence indicators turn off when an agent disconnects.
            $schedule->command('helpdesk:agents:cleanup-presence')->everyMinute();

            // GDPR retention: hard-purge customers soft-deleted (anonymised) more
            // than the retention window ago. Promised to the user at deletion time
            // ("eliminados definitivamente en 90 días") — must actually run.
            $schedule->command('helpdesk:purge-old-gdpr-deletes')
                ->dailyAt('04:00')
                ->onOneServer();

            // Antes solo se sincronizaba con un botón manual en Settings — nunca
            // se había ejecutado, la tabla local quedó con datos de demo (seeder)
            // en vez de las plantillas reales aprobadas por Meta.
            $schedule->job(new SyncWhatsAppTemplatesJob())
                ->dailyAt('05:00')
                ->onOneServer();
        });

        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Conversation::class => ConversationPolicy::class,
            Customer::class => CustomerPolicy::class,
            ConversationStatus::class => ConversationStatusPolicy::class,
            ConversationTag::class => ConversationTagPolicy::class,
            ConversationView::class => ConversationViewPolicy::class,
            Group::class => GroupPolicy::class,
            Webhook::class => WebhookPolicy::class,
            CustomAttribute::class => CustomAttributePolicy::class,
            CannedReply::class => CannedReplyPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model)) {
                Gate::policy($model, $policy);
            }
        }
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
