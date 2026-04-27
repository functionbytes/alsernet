<?php

namespace Modules\Faqs\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Faqs\Models\Faq;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class FaqsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Faqs';

    protected string $moduleNameLower = 'faqs';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerShortcodes();
    }

    public function register(): void {}

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

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->moduleNameLower
        );
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem($this->moduleNameLower, [
            'icon' => 'fas fa-circle-question',
            'tooltip' => 'FAQs',
            'sidebar_id' => $this->moduleNameLower,
            'order' => 66,
        ]);

        NavService::registerSidebar($this->moduleNameLower, [
            'title' => 'FAQs',
            'items' => [
                ['label' => 'Preguntas', 'route' => $this->moduleNameLower.'.index', 'permission' => 'faqs.view'],
                ['label' => 'Nueva pregunta', 'route' => $this->moduleNameLower.'.create', 'permission' => 'faqs.create'],
                ['label' => 'Categorías', 'route' => $this->moduleNameLower.'.categories.index', 'permission' => 'faqs.categories.view'],
            ],
        ]);

        NavService::addItemsToSection('settings', 'Configuraciones', [
            ['label' => 'FAQ', 'route' => 'settings.faqs.index'],
        ]);
    }

    protected function registerShortcodes(): void
    {
        $this->app->booted(function () {
            if (! $this->app->bound('shortcode')) {
                return;
            }

            app('shortcode')->register('faqs', function (array $attrs) {
                static $instanceCounter = 0;
                $instanceId = ++$instanceCounter;

                $categoryId = isset($attrs['category_id']) ? (int) $attrs['category_id'] : null;
                $limit = isset($attrs['limit']) ? (int) $attrs['limit'] : 0;
                $title = $attrs['title'] ?? null;

                $query = Faq::query()
                    ->published()
                    ->with('translations')
                    ->orderBy('order')
                    ->orderBy('id');

                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }

                if ($limit > 0) {
                    $query->limit($limit);
                }

                $faqs = $query->get();

                return view('faqs::shortcodes.accordion', [
                    'faqs' => $faqs,
                    'title' => $title,
                    'instanceId' => $instanceId,
                ])->render();
            }, [
                'description' => 'Muestra preguntas frecuentes en formato acordeón.',
                'example' => '[faqs /]',
                'cacheable' => false,
                'attributes' => [
                    'category_id' => 'ID de la categoría a filtrar (opcional)',
                    'limit' => 'Número máximo de preguntas a mostrar (default: todas)',
                    'title' => 'Título opcional sobre el acordeón',
                ],
            ]);
        });
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
