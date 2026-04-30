<?php

namespace Modules\Chat\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Chat\Console\Commands\CheckSlaBreaches;
use Modules\Chat\Console\Commands\FetchEmailsCommand;
use Modules\Chat\Console\Commands\GenerateAgentPerformanceSnapshots;
use Modules\Chat\Console\Commands\RunAutomationScheduleds;
use Modules\Chat\Http\ViewComposers\NavigationComposer;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Permission;
use Modules\Chat\Models\Webhook;
use Modules\Chat\Observers\ConversationObserver;
use Modules\Chat\Observers\MessageObserver;
use Modules\Chat\Policies\SettingsPolicy;
use Modules\Chat\Policies\WebhookPolicy;
use Modules\Chat\Services\ConversationStatsService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ChatServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Chat';

    protected string $nameLower = 'Chat';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/Chat.php', 'Chat');
        $this->mergeConfigFrom(__DIR__.'/../../config/channels.php', 'channels');

        $this->app->register(AuthServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->registerPolicies();

        $this->app->bind(ConversationStatsService::class, function ($app) {
            $accountId = auth()->check() ? auth()->user()->account_id : 1;

            return new ConversationStatsService($accountId);
        });
    }

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->registerGates();
        $this->registerRateLimiters();
        $this->registerMenus();
        $this->registerHelpers();
        $this->publishAssets();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerRoutes();

        Conversation::observe(ConversationObserver::class);
        ConversationMessage::observe(MessageObserver::class);
    }

    protected function registerPolicies(): void
    {
        if (class_exists(Webhook::class)) {
            Gate::policy(Webhook::class, WebhookPolicy::class);
        }
    }

    protected function registerGates(): void
    {
        $settingsPolicy = new SettingsPolicy;

        Gate::define('configure-chat', fn ($user) => $settingsPolicy->configure($user));
        Gate::define('view-chat-settings', fn ($user) => $settingsPolicy->viewSettings($user));
        Gate::define('manage-teams', fn ($user) => $settingsPolicy->manageTeams($user));
        Gate::define('manage-channels', fn ($user) => $settingsPolicy->manageChannels($user));
        Gate::define('manage-templates', fn ($user) => $settingsPolicy->manageTemplates($user));
        Gate::define('manage-automations', fn ($user) => $settingsPolicy->manageAutomations($user));
        Gate::define('manage-webhooks', fn ($user) => $settingsPolicy->manageWebhooks($user));
        Gate::define('view-chat-reports', fn ($user) => $settingsPolicy->viewReports($user));
        Gate::define('export-chat-reports', fn ($user) => $settingsPolicy->exportReports($user));
        Gate::define('view-chat-audits', fn ($user) => $settingsPolicy->viewAuditLogs($user));
        Gate::define('manage-business-hours', fn ($user) => $settingsPolicy->manageBusinessHours($user));
        Gate::define('manage-notifications', fn ($user) => $settingsPolicy->manageNotifications($user));
        Gate::define('manage-sla-policies', fn ($user) => $settingsPolicy->manageSLAPolicies($user));

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-chats')) {
                return true;
            }

            if (class_exists('Modules\Chat\Models\Permission')) {
                $permission = Permission::where('name', $ability)->first();

                if ($permission) {
                    return $user->permissions()->where('name', $ability)->exists();
                }
            }

            return null;
        });
    }

    protected function registerRoutes(): void
    {
        require module_path($this->name, 'routes/web.php');

        Route::prefix('api')
            ->middleware('throttle:300,1')
            ->group(function () {
                require module_path($this->name, 'routes/api.php');
            });
    }

    protected function registerRateLimiters(): void
    {
        $byUser = fn (int $perMinute) => fn (Request $request) => Limit::perMinute($perMinute)
            ->by(optional($request->user())->id ?: $request->ip());

        RateLimiter::for('chat.settings', $byUser(120));
        RateLimiter::for('chat.sensitive', $byUser(30));
        RateLimiter::for('chat.imports', $byUser(10));
        RateLimiter::for('chat.webhooks', $byUser(60));
    }

    protected function registerViewComposers(): void
    {
        view()->composer('theme::theme.includes.nav', NavigationComposer::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            GenerateAgentPerformanceSnapshots::class,
            FetchEmailsCommand::class,
            RunAutomationScheduleds::class,
            CheckSlaBreaches::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('chat:fetch-emails')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
            $schedule->command('chat:check-sla-breaches')->everyTenMinutes()->withoutOverlapping()->runInBackground();
            $schedule->command('chat:run-automation-scheduled')->everyMinute()->withoutOverlapping()->runInBackground();
            $schedule->command('chat:generate-agent-performance-snapshots')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
        });
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

    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('chat', [
            'icon' => 'fa-headset',
            'tooltip' => 'Chat',
            'sidebar_id' => 'chat',
            'order' => 30,
            'module' => 'Chat',
            'route' => 'chat.conversations.index',
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Configuración',
            'module' => 'Chat',
            'items' => [
                ['label' => 'Canales', 'route' => 'settings.chat.channels.add', 'permission' => 'manage-channels'],
                ['label' => 'Equipos', 'route' => 'settings.chat.teams.index', 'permission' => 'manage-teams'],
                ['label' => 'Etiquetas', 'route' => 'settings.chat.labels.index'],
                ['label' => 'Configuración general', 'route' => 'settings.chat.configurations.global', 'permission' => 'configure-chat'],
                ['label' => 'Automatizaciones', 'route' => 'settings.chat.automation-rules.index', 'permission' => 'manage-automations'],
                ['label' => 'Macros', 'route' => 'settings.chat.macros.index', 'permission' => 'manage-automations'],
                ['label' => 'Plantillas de mensaje', 'route' => 'settings.chat.message.index', 'permission' => 'manage-templates'],
                ['label' => 'Plantillas de email', 'route' => 'settings.chat.email.index', 'permission' => 'manage-templates'],
                ['label' => 'Horarios de negocio', 'route' => 'settings.chat.business-hours', 'permission' => 'manage-business-hours'],
                ['label' => 'Notificaciones', 'route' => 'settings.chat.notifications', 'permission' => 'manage-notifications'],
                ['label' => 'Atributos personalizados', 'route' => 'settings.chat.custom-attributes.index', 'permission' => 'view-chat-settings'],
                ['label' => 'Roles de equipo', 'route' => 'settings.chat.team-roles.index', 'permission' => 'manage-teams'],
                ['label' => 'Políticas SLA', 'route' => 'settings.chat.sla-policies.index', 'permission' => 'manage-sla-policies'],
                ['label' => 'Webhooks', 'route' => 'settings.chat.webhooks.index', 'permission' => 'manage-webhooks'],
                ['label' => 'Integraciones', 'route' => 'settings.chat.integrations.index', 'permission' => 'view-chat-settings'],
                ['label' => 'Registros de auditoría', 'route' => 'settings.chat.audits.index', 'permission' => 'view-chat-audits'],
            ],
        ]);
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

    private function registerHelpers(): void
    {
        $helperFile = __DIR__.'/../Helpers/helpers.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    private function publishAssets(): void
    {
        $this->publishes([__DIR__.'/../../public' => public_path()], 'chat-assets');
    }
}
