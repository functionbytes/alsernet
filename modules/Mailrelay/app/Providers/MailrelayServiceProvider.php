<?php

namespace Modules\Mailrelay\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Mailrelay\Console\Commands\ListProvidersCommand;
use Modules\Mailrelay\Console\Commands\SendCampaigns;
use Modules\Mailrelay\Console\Commands\SyncMailrelayCommand;
use Modules\Mailrelay\Console\Commands\TestProviderCommand;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\ImportJob;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Http\ViewComposers\NavigationComposer;
use Modules\Mailrelay\Policies\CampaignPolicy;
use Modules\Mailrelay\Policies\ImportPolicy;
use Modules\Mailrelay\Policies\MailProviderPolicy;
use Modules\Mailrelay\Policies\SubscriberPolicy;
use Modules\Theme\Services\NavService;

class MailrelayServiceProvider extends ServiceProvider
{
    /**
     * Module namespace
     */
    protected string $moduleNamespace = 'Modules\Mailrelay\Http\Controllers';

    /**
     * Module name
     */
    protected string $moduleName = 'Mailrelay';

    /**
     * Module name (lowercase)
     */
    protected string $moduleNameLower = 'mailrelay';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mailrelay.php',
            'mailrelay'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/email-validator.php',
            'email-validator'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/email-utilities.php',
            'email-utilities'
        );

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        // Note: Services are NOT registered as singletons here to prevent boot-time errors
        // when configuration values (like API keys) are not yet available.
        // Laravel will auto-resolve these services when needed with proper configuration.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Only boot module features if Mailrelay module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('Mailrelay')) {
            return;
        }

        // Register translations
        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);

        // Register views
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);

        // Register view composers
        $this->registerViewComposers();

        // Register migrations
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Register policies
        $this->registerPolicies();

        // Register gates
        $this->registerGates();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Register menu items with NavService
        $this->registerNavigation();

        // Publish assets
        if ($this->app->runningInConsole()) {
            $this->publishAssets();
        }

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register module routes
     *
     * Routes are loaded after boot to ensure router is ready
     * Middleware applied within routes/web.php file
     */
    protected function registerRoutes(): void
    {
        // Web routes (operational /mailrelay and configuration /settings/mailrelay)
        require module_path($this->moduleName, 'routes/web.php');

        // API routes
        Route::prefix('api')
            ->middleware('api')
            ->name('api.')
            ->group(function () {
                $apiPath = module_path($this->moduleName, 'routes/api.php');
                if (file_exists($apiPath)) {
                    require $apiPath;
                }
            });
    }

    /**
     * Register artisan commands
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SyncMailrelayCommand::class,
            SendCampaigns::class,
            ListProvidersCommand::class,
            TestProviderCommand::class,
        ]);
    }

    /**
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        view()->composer('*', NavigationComposer::class);
    }

    /**
     * Register authorization policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Subscriber::class, SubscriberPolicy::class);
        Gate::policy(ImportJob::class, ImportPolicy::class);
        Gate::policy(MailProvider::class, MailProviderPolicy::class);
    }

    /**
     * Register authorization gates
     *
     * Uses standard Spatie Permission pattern with Gate::before()
     * for dynamic permission checking
     */
    protected function registerGates(): void
    {
        // Dynamic gate handler for all mailrelay permissions
        Gate::before(function ($user, $ability) {
            // Super-settings bypass
            if ($user->hasRole('super-settings')) {
                return true;
            }

            // Check if this is a mailrelay permission
            if (str_starts_with($ability, 'mailrelay.')) {
                try {
                    return $user->hasPermissionTo($ability);
                } catch (\Exception $e) {
                    // Permission doesn't exist in database yet
                    return false;
                }
            }

            return null; // Let other gates/policies handle it
        });
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @mailrelayStatus directive for status badges
        Blade::directive('mailrelayStatus', function ($expression) {
            return "<?php echo view('mailrelay::components.status-badge', ['status' => $expression])->render(); ?>";
        });

        // @campaignAnalytics directive
        Blade::directive('campaignAnalytics', function ($expression) {
            return "<?php echo view('mailrelay::components.campaign-analytics', ['campaign' => $expression])->render(); ?>";
        });
    }

    /**
     * Register scheduled tasks
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Sync with Mailrelay every hour
            if (config('mailrelay.sync.enabled')) {
                $schedule->command('mailrelay:sync')
                    ->hourly()
                    ->withoutOverlapping()
                    ->runInBackground();
            }

            // Send scheduled campaigns every 15 minutes
            $schedule->command('mailrelay:send-campaigns')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    /**
     * Register navigation menu items
     */
    protected function registerNavigation(): void
    {
        // Integration with NavService for sidebar menu
        if (class_exists('\Modules\Theme\Services\NavService')) {
            // Mini-nav item for Mailrelay (operational)
            NavService::registerMiniItem('mailrelay', [
                'icon' => 'fas fa-envelope-open-text',
                'tooltip' => 'Email Marketing',
                'sidebar_id' => 'mailrelay',
                'order' => 60,
            ]);

            // Sidebar local - Mailrelay (operational)
            NavService::registerSidebar('mailrelay', [
                'title' => 'Email Marketing',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'mailrelay.dashboard', 'permission' => 'mailrelay.access'],
                    ['label' => 'Campañas', 'route' => 'mailrelay.campaigns.index', 'permission' => 'mailrelay.campaigns.view'],
                    ['label' => 'Suscriptores', 'route' => 'mailrelay.subscribers.index', 'permission' => 'mailrelay.subscribers.view'],
                    ['label' => 'Importaciones', 'route' => 'mailrelay.imports.index', 'permission' => 'mailrelay.imports.create'],
                    ['label' => 'Validación', 'route' => 'mailrelay.validation.test', 'permission' => 'mailrelay.access'],
                ],
            ]);

            // Add Mailrelay settings to the generic 'settings' sidebar
            NavService::registerSidebar('settings', [
                'title' => 'Email Marketing',
                'items' => [
                    ['label' => 'Configuración general', 'route' => 'settings.mailrelay.general.index'],
                    ['label' => 'Configuración API', 'route' => 'settings.mailrelay.api.index'],
                    ['label' => 'Plantillas de email', 'route' => 'settings.mailrelay.templates.index'],
                    ['label' => 'Grupos de suscriptores', 'route' => 'settings.mailrelay.groups.index'],
                    ['label' => 'Campos personalizados', 'route' => 'settings.mailrelay.custom-fields.index'],
                    ['label' => 'Automatizaciones', 'route' => 'settings.mailrelay.automations.index'],
                    ['label' => 'Webhooks', 'route' => 'settings.mailrelay.webhooks.index'],
                    ['label' => 'Permisos', 'route' => 'settings.mailrelay.permissions.index'],
                ],
            ]);
        }
    }

    /**
     * Publish module assets
     */
    protected function publishAssets(): void
    {
        // Publish configuration
        $this->publishes([
            module_path($this->moduleName, 'config/mailrelay.php') => config_path('mailrelay.php'),
            module_path($this->moduleName, 'config/email-validator.php') => config_path('email-validator.php'),
            module_path($this->moduleName, 'config/email-utilities.php') => config_path('email-utilities.php'),
        ], 'mailrelay-config');

        // Publish migrations
        $this->publishes([
            module_path($this->moduleName, 'database/migrations') => database_path('migrations'),
        ], 'mailrelay-migrations');

        // Publish views
        $this->publishes([
            module_path($this->moduleName, 'resources/views') => resource_path('views/vendor/mailrelay'),
        ], 'mailrelay-views');

        // Publish public assets
        $this->publishes([
            module_path($this->moduleName, 'public') => public_path('modules/mailrelay'),
        ], 'mailrelay-assets');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }
}
