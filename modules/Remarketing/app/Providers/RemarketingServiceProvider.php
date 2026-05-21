<?php

namespace Modules\Remarketing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\Remarketing\Channels\EmailChannel;
use Modules\Remarketing\Console\Commands\CalculateRfmCommand;
use Modules\Remarketing\Console\Commands\MarkAbandonedCartsCommand;
use Modules\Remarketing\Console\Commands\PopulateProductWatchesCommand;
use Modules\Remarketing\Console\Commands\ProcessAutomationsCommand;
use Modules\Remarketing\Console\Commands\ReconcileCatalogCommand;
use Modules\Remarketing\Jobs\DetectBrowseAbandonmentJob;
use Modules\Remarketing\Jobs\DetectOrderAnniversaryJob;
use Modules\Remarketing\Jobs\DetectReplenishmentJob;
use Modules\Remarketing\Jobs\DetectWinBackJob;
use Modules\Remarketing\Listeners\ApologyAfterReturnListener;
use Modules\Remarketing\Listeners\CheckVipMilestoneListener;
use Modules\Remarketing\Listeners\CrossSellOnDeliveredListener;
use Modules\Remarketing\Listeners\NotifyBackInStockToWatchersListener;
use Modules\Remarketing\Listeners\NotifyPriceDropToWatchersListener;
use Modules\Remarketing\Listeners\RecoverAbandonedCartListener;
use Modules\Remarketing\Listeners\RequestReviewOnDeliveredListener;
use Modules\Remarketing\Listeners\SendWelcomeOnFirstOrderListener;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use Modules\Remarketing\Models\Template;
use Modules\Remarketing\Observers\ConsentEventObserver;
use Modules\Remarketing\Observers\OrderObserver;
use Modules\Remarketing\Policies\AutomationPolicy;
use Modules\Remarketing\Policies\CampaignPolicy;
use Modules\Remarketing\Policies\CustomerPolicy;
use Modules\Remarketing\Policies\ReportPolicy;
use Modules\Remarketing\Policies\SegmentPolicy;
use Modules\Remarketing\Policies\StorePolicy;
use Modules\Remarketing\Policies\SuppressionPolicy;
use Modules\Remarketing\Policies\TemplatePolicy;
use Modules\Remarketing\Services\ChannelRegistry;
use Modules\Remarketing\Services\StepHandlerRegistry;
use Modules\Remarketing\Steps\SendEmailStepHandler;
use Modules\Remarketing\Steps\WaitStepHandler;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RemarketingServiceProvider extends ServiceProvider
{
    protected string $name = 'Remarketing';

    protected string $nameLower = 'remarketing';

    public function boot(): void
    {
        if (Module::find('Remarketing')?->isDisabled()) {
            return;
        }

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerNavService();
        $this->registerObservers();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->app->singleton(ChannelRegistry::class, function () {
            $registry = new ChannelRegistry;
            $registry->register(new EmailChannel);

            return $registry;
        });

        $this->app->singleton(StepHandlerRegistry::class, function ($app) {
            $registry = new StepHandlerRegistry;
            $registry->register('wait', new WaitStepHandler);
            $registry->register('send_email', new SendEmailStepHandler($app->make(ChannelRegistry::class)));

            return $registry;
        });

        $this->registerPolicies();
        $this->registerSchedule();
        $this->registerCommands();
    }

    protected function registerNavService(): void
    {
        NavService::registerMiniItem('remarketing', [
            'icon' => 'fa-duotone fa-thin fa-bullhorn',
            'tooltip' => 'Remarketing',
            'sidebar_id' => 'remarketing',
            'order' => 50,
        ]);

        NavService::registerSidebar('remarketing', [
            'title' => 'Remarketing',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'remarketing.dashboard',
                    'permission' => 'remarketing.view',
                    'icon' => 'fas fa-gauge-high',
                ],
                [
                    'label' => 'Tiendas',
                    'route' => 'remarketing.stores.index',
                    'permission' => 'remarketing.stores.view',
                    'icon' => 'fas fa-store',
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'remarketing.customers.index',
                    'permission' => 'remarketing.customers.view',
                    'icon' => 'fas fa-users',
                ],
                [
                    'label' => 'Productos',
                    'route' => 'remarketing.products.index',
                    'permission' => 'remarketing.products.view',
                    'icon' => 'fas fa-box',
                ],
                [
                    'label' => 'Carritos',
                    'route' => 'remarketing.carts.index',
                    'permission' => 'remarketing.view',
                    'icon' => 'fas fa-cart-shopping',
                ],
                [
                    'label' => 'Segmentos',
                    'route' => 'remarketing.segments.index',
                    'permission' => 'remarketing.segments.view',
                    'icon' => 'fas fa-layer-group',
                ],
                [
                    'label' => 'Campañas',
                    'route' => 'remarketing.campaigns.index',
                    'permission' => 'remarketing.campaigns.view',
                    'icon' => 'fas fa-paper-plane',
                ],
                [
                    'label' => 'Automations',
                    'route' => 'remarketing.automations.index',
                    'permission' => 'remarketing.automations.view',
                    'icon' => 'fas fa-robot',
                ],
                [
                    'label' => 'Plantillas',
                    'route' => 'remarketing.templates.index',
                    'permission' => 'remarketing.templates.view',
                    'icon' => 'fas fa-envelope-open-text',
                ],
                [
                    'label' => 'Reportes',
                    'route' => 'remarketing.reports.index',
                    'permission' => 'remarketing.reports.view',
                    'icon' => 'fas fa-chart-line',
                ],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Remarketing',
            'items' => [
                [
                    'label' => 'General',
                    'route' => 'settings.remarketing.general',
                    'permission' => 'remarketing.settings.view',
                ],
                [
                    'label' => 'Consent',
                    'route' => 'settings.remarketing.consent',
                    'permission' => 'remarketing.settings.view',
                ],
                [
                    'label' => 'Suppressions',
                    'route' => 'settings.remarketing.suppressions',
                    'permission' => 'remarketing.suppressions.view',
                ],
            ],
        ]);
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Store::class => StorePolicy::class,
            Customer::class => CustomerPolicy::class,
            Segment::class => SegmentPolicy::class,
            Campaign::class => CampaignPolicy::class,
            Automation::class => AutomationPolicy::class,
            Template::class => TemplatePolicy::class,
            Suppression::class => SuppressionPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model)) {
                Gate::policy($model, $policy);
            }
        }

        // ReportPolicy has no model — register the viewAny gate directly
        Gate::define('viewAny-report', [ReportPolicy::class, 'viewAny']);
    }

    protected function registerObservers(): void
    {
        Order::observe(OrderObserver::class);
        ConsentEvent::observe(ConsentEventObserver::class);
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('remarketing:mark-abandoned-carts')
                ->everyFifteenMinutes()
                ->withoutOverlapping();

            $schedule->command('remarketing:process-automations')
                ->everyMinute()
                ->withoutOverlapping();

            $schedule->command('remarketing:calculate-rfm')
                ->dailyAt('03:00')
                ->withoutOverlapping();

            $schedule->command('remarketing:reconcile-catalog')
                ->dailyAt('04:00')
                ->withoutOverlapping();

            // Smart triggers (PS-aware)
            if (class_exists(DetectReplenishmentJob::class)) {
                $schedule->job(new DetectReplenishmentJob)
                    ->dailyAt('09:00')
                    ->withoutOverlapping();
            }
            if (class_exists(DetectWinBackJob::class)) {
                $schedule->job(new DetectWinBackJob)
                    ->dailyAt('10:00')
                    ->withoutOverlapping();
            }

            if (class_exists(DetectBrowseAbandonmentJob::class)) {
                $schedule->job(new DetectBrowseAbandonmentJob)
                    ->hourly()
                    ->withoutOverlapping();
            }

            if (class_exists(DetectOrderAnniversaryJob::class)) {
                $schedule->job(new DetectOrderAnniversaryJob)
                    ->dailyAt('11:00')
                    ->withoutOverlapping();
            }
        });
    }

    protected function registerCommands(): void
    {
        $commands = [
            ProcessAutomationsCommand::class,
            CalculateRfmCommand::class,
            ReconcileCatalogCommand::class,
            MarkAbandonedCartsCommand::class,
            PopulateProductWatchesCommand::class,
        ];

        $existing = array_filter($commands, fn (string $class) => class_exists($class));

        if (! empty($existing)) {
            $this->commands($existing);
        }

        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        $events = [
            PsOrderCreated::class => [
                SendWelcomeOnFirstOrderListener::class,
                CheckVipMilestoneListener::class,
            ],
            PsOrderStatusChanged::class => [
                RequestReviewOnDeliveredListener::class,
                CrossSellOnDeliveredListener::class,
            ],
            PsOrderReturned::class => [
                ApologyAfterReturnListener::class,
            ],
            PsCartAbandoned::class => [
                RecoverAbandonedCartListener::class,
            ],
            PsPriceDropped::class => [
                NotifyPriceDropToWatchersListener::class,
            ],
            PsBackInStock::class => [
                NotifyBackInStockToWatchersListener::class,
            ],
        ];

        foreach ($events as $event => $listeners) {
            if (! class_exists($event)) {
                continue;
            }
            foreach ($listeners as $listener) {
                if (class_exists($listener)) {
                    Event::listen($event, $listener);
                }
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

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$configKey);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFrom($file->getPathname(), $key);
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
