<?php

namespace Modules\HelpdeskHelpcenter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskHelpcenter\Console\Commands\ReindexArticleEmbeddingsCommand;
use Modules\HelpdeskHelpcenter\Http\Controllers\Managers\HelpCenterController;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleTranslation;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleVote;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;
use Modules\HelpdeskHelpcenter\Observers\HelpCenterArticleObserver;
use Modules\HelpdeskHelpcenter\Observers\HelpCenterArticleTranslationObserver;
use Modules\HelpdeskHelpcenter\Observers\HelpCenterArticleVoteObserver;
use Modules\HelpdeskHelpcenter\Policies\HelpCenterArticlePolicy;
use Modules\HelpdeskHelpcenter\Policies\HelpCenterCategoryPolicy;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskHelpcenterServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskHelpcenter';

    protected string $moduleNameLower = 'helpdeskhelpcenter';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerMenus();
        $this->registerCommands();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    protected function registerObservers(): void
    {
        HelpCenterArticle::observe(HelpCenterArticleObserver::class);
        HelpCenterArticleVote::observe(HelpCenterArticleVoteObserver::class);
        HelpCenterArticleTranslation::observe(HelpCenterArticleTranslationObserver::class);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ReindexArticleEmbeddingsCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('helpcenter:embeddings:reindex')->weeklyOn(0, '03:00');
        });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    public function register(): void
    {
        $this->app->singleton(HelpcenterWidgetService::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'config/config.php');

        if (! file_exists($configPath)) {
            return;
        }

        $this->publishes([$configPath => config_path('helpdeskhelpcenter.php')], 'config');
        $this->mergeConfigFrom($configPath, 'helpdeskhelpcenter');
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $publishPath = resource_path('views/modules/helpdeskhelpcenter');
        $this->publishes([$sourcePath => $publishPath], ['views', 'helpdeskhelpcenter-module-views']);

        $publishedPaths = array_values(array_filter(
            array_map(
                fn ($p) => is_dir($p.'/modules/helpdeskhelpcenter') ? $p.'/modules/helpdeskhelpcenter' : null,
                config('view.paths'),
            ),
        ));

        $this->loadViewsFrom([...$publishedPaths, $sourcePath], 'helpdeskhelpcenter');
    }

    protected function registerRoutes(): void
    {
        $this->loadManagerRoutes();
        $this->loadAgentRoutes();
        $this->loadPublicRoutes();
    }

    protected function loadManagerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/managers.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])
            ->prefix('panel/helpdesk')
            ->group($path);
    }

    protected function loadAgentRoutes(): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('panel/helpdesk/helpcenter')
            ->group(function () {
                Route::get('/articles/search', [HelpCenterController::class, 'searchArticles'])
                    ->name('manager.helpcenter.articles.search');
            });
    }

    protected function loadPublicRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/public.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web'])
            ->group($path);
    }

    protected function registerPolicies(): void
    {
        $map = [
            HelpCenterArticle::class => HelpCenterArticlePolicy::class,
            HelpCenterCategory::class => HelpCenterCategoryPolicy::class,
        ];

        foreach ($map as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    protected function registerMenus(): void
    {
        NavService::registerSidebar('helpdesk', [
            'title' => 'Centro de conocimiento',
            'items' => [
                [
                    'label' => 'Inicio',
                    'route' => 'manager.helpcenter.index',
                    'icon' => 'far fa-book-open',
                    'permission' => 'helpdesk.helpcenter.view',
                ],
                [
                    'label' => 'Categorías',
                    'route' => 'manager.helpcenter.categories',
                    'icon' => 'far fa-folder-tree',
                    'permission' => 'helpdesk.helpcenter.categories.view',
                ],
                [
                    'label' => 'Artículos',
                    'route' => 'manager.helpcenter.articles',
                    'icon' => 'far fa-file-lines',
                    'permission' => 'helpdesk.helpcenter.articles.view',
                ],
            ],
        ]);
    }
}
