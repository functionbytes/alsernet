<?php

namespace Modules\Forms\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Forms\Console\Commands\CleanupAbandonedFormsCommand;
use Modules\Forms\Console\Commands\CleanupFormDataCommand;
use Modules\Forms\Console\Commands\CleanupFormTokensCommand;
use Modules\Forms\Console\Commands\FormsInstallCommand;
use Modules\Forms\Console\Commands\ProcessFormFollowUpsCommand;
use Modules\Forms\Console\Commands\PruneFormVersionsCommand;
use Modules\Forms\Console\Commands\SendAbandonReminderCommand;
use Modules\Forms\Models\Form;
use Modules\Forms\Policies\FormPolicy;
use Modules\Forms\Services\FormEmailService;
use Modules\Forms\Services\FormSubmissionService;
use Modules\Forms\Services\FormWebhookService;
use Modules\Page\Http\Controllers\VisualEditorController;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class FormsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Forms';

    protected string $moduleNameLower = 'forms';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if (Module::find('Forms')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerMenus();
        $this->registerShortcodes();
        $this->registerCommands();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(FormEmailService::class);
        $this->app->singleton(FormWebhookService::class);
        $this->app->singleton(FormSubmissionService::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->moduleName, 'routes/api.php'));
    }

    /**
     * Register the form shortcode.
     *
     * Usage: [form id="1" /] or [form slug="contact" theme="flat" display="popup" /]
     */
    protected function registerShortcodes(): void
    {
        $this->app->booted(function () {
            if (! $this->app->bound('shortcode')) {
                return;
            }

            app('shortcode')->register('form', function (array $attrs) {
                $shortcodeConfig = [
                    'display' => $attrs['display'] ?? 'inline',
                    'columns' => $attrs['columns'] ?? 1,
                    'show_title' => filter_var($attrs['show_title'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'button_text' => $attrs['button_text'] ?? null,
                    'theme' => $attrs['theme'] ?? null,
                ];

                if (! empty($attrs['id'])) {
                    $cacheKey = 'forms:active:'.(int) $attrs['id'];
                } elseif (! empty($attrs['slug'])) {
                    $cacheKey = 'forms:active:slug:'.$attrs['slug'];
                } else {
                    return '';
                }

                $form = Cache::remember($cacheKey, 300, function () use ($attrs) {
                    $query = Form::with('fields')->active();

                    if (! empty($attrs['id'])) {
                        $query->where('id', (int) $attrs['id']);
                    } else {
                        $query->where('slug', $attrs['slug']);
                    }

                    return $query->first();
                });

                if (! $form) {
                    return '';
                }

                $html = view('forms::public.render', compact('form', 'shortcodeConfig'))->render();

                if (app()->bound('ve_preview_wrap')) {
                    $safe = VisualEditorController::buildVeScTag('form', $attrs, '');

                    return '<div data-ve-sc="'.$safe.'">'.$html.'</div>';
                }

                return $html;
            });
        });
    }

    /**
     * Register sidebar menu entries.
     */
    protected function registerMenus(): void
    {
        NavService::registerMiniItem('forms-inbox', [
            'icon' => 'fas fa-inbox',
            'tooltip' => 'Inbox formularios',
            'sidebar_id' => 'forms-inbox',
            'order' => 46,
        ]);

        NavService::registerSidebar('forms-inbox', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'forms.inbox.dashboard'],
                ['label' => 'Todas las submissions', 'route' => 'forms.inbox.index'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Todos los formularios', 'route' => 'settings.forms.index'],
                ['label' => 'Categorias', 'route' => 'settings.forms.categories.index'],
                ['label' => 'Biblioteca de plantillas', 'route' => 'settings.forms.templates-library.index'],
            ],
        ]);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessFormFollowUpsCommand::class,
                SendAbandonReminderCommand::class,
                CleanupFormDataCommand::class,
                FormsInstallCommand::class,
                CleanupAbandonedFormsCommand::class,
                CleanupFormTokensCommand::class,
                PruneFormVersionsCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
