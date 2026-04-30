<?php

namespace Modules\Helpdesk\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Console\Commands\FetchEmailTicketsCommand;
use Modules\Helpdesk\Http\ViewComposers\NavigationComposer;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\CustomAttribute;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\HelpCenterCategory;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Policies\CannedReplyPolicy;
use Modules\Helpdesk\Policies\ConversationPolicy;
use Modules\Helpdesk\Policies\ConversationStatusPolicy;
use Modules\Helpdesk\Policies\ConversationTagPolicy;
use Modules\Helpdesk\Policies\ConversationViewPolicy;
use Modules\Helpdesk\Policies\CustomAttributePolicy;
use Modules\Helpdesk\Policies\CustomerPolicy;
use Modules\Helpdesk\Policies\GroupPolicy;
use Modules\Helpdesk\Policies\HelpCenterArticlePolicy;
use Modules\Helpdesk\Policies\HelpCenterCategoryPolicy;
use Modules\Helpdesk\Policies\WebhookPolicy;
use Modules\Helpdesk\Services\CannedReplyService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CustomerStatsService;
use Modules\Helpdesk\Services\EmailInboundService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\NotificationService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
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
        $this->registerViewComposers();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerHelpdeskSidebar();
        $this->registerSettingsSidebar();
    }

    /**
     * Items del módulo Helpdesk en el sidebar principal (sección "Centro de ayuda").
     */
    protected function registerHelpdeskSidebar(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Centro de ayuda',
            'items' => [
                [
                    'label' => 'Inicio',
                    'route' => 'manager.helpdesk.helpcenter.index',
                    'icon' => 'far fa-book-open',
                    'permission' => 'helpdesk.helpcenter.view',
                ],
                [
                    'label' => 'Categorías',
                    'route' => 'manager.helpdesk.helpcenter.categories',
                    'icon' => 'far fa-folder-tree',
                    'permission' => 'helpdesk.helpcenter.categories.view',
                ],
                [
                    'label' => 'Artículos',
                    'route' => 'manager.helpdesk.helpcenter.articles',
                    'icon' => 'far fa-file-lines',
                    'permission' => 'helpdesk.helpcenter.articles.view',
                ],
            ],
        ]);
    }

    /**
     * Items del módulo Helpdesk en el sidebar de Configuración.
     */
    protected function registerSettingsSidebar(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk',
            'items' => [
                ['label' => 'Configuración de tickets', 'route' => 'settings.helpdesk.tickets', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Chat en vivo', 'route' => 'settings.helpdesk.livechat', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Inteligencia artificial', 'route' => 'settings.helpdesk.ai', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Subida de archivos', 'route' => 'settings.helpdesk.uploading', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Integraciones sociales', 'route' => 'settings.helpdesk.social-integrations.index', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Atributos personalizados', 'route' => 'settings.helpdesk.attributes.index', 'permission' => 'helpdesk.attributes.view'],
                ['label' => 'Etiquetas', 'route' => 'settings.helpdesk.tags.index', 'permission' => 'helpdesk.tags.view'],
                ['label' => 'Estados de conversación', 'route' => 'settings.helpdesk.statuses.index', 'permission' => 'helpdesk.statuses.view'],
                ['label' => 'Vistas personalizadas', 'route' => 'settings.helpdesk.views.index', 'permission' => 'helpdesk.views.view'],
                ['label' => 'Equipo - Miembros', 'route' => 'settings.helpdesk.team.members', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Equipo - Grupos', 'route' => 'settings.helpdesk.team.groups', 'permission' => 'helpdesk.settings.view'],
                ['label' => 'Webhooks', 'route' => 'settings.helpdesk.webhooks.index', 'permission' => 'helpdesk.webhooks.view'],
            ],
        ]);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register services as singletons
        $this->app->singleton(ConversationTagService::class);
        $this->app->singleton(CustomerStatsService::class);
        $this->app->singleton(CannedReplyService::class);
        $this->app->singleton(NotificationService::class);

        // Social media integration services
        $this->app->singleton(WhatsAppBusinessService::class);
        $this->app->singleton(FacebookMessengerService::class);
        $this->app->singleton(InstagramService::class);
        $this->app->singleton(OutboundMessageService::class);

        // Email inbound service
        $this->app->singleton(EmailInboundService::class);

        $this->commands([FetchEmailTicketsCommand::class]);

        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Conversation::class => ConversationPolicy::class,
            Customer::class => CustomerPolicy::class,
            HelpCenterArticle::class => HelpCenterArticlePolicy::class,
            ConversationStatus::class => ConversationStatusPolicy::class,
            ConversationTag::class => ConversationTagPolicy::class,
            ConversationView::class => ConversationViewPolicy::class,
            Group::class => GroupPolicy::class,
            Webhook::class => WebhookPolicy::class,
            HelpCenterCategory::class => HelpCenterCategoryPolicy::class,
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
