<?php

namespace Modules\Forms\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
use Modules\Forms\Services\FormAnalyticsService;
use Modules\Forms\Services\FormEmailService;
use Modules\Forms\Services\FormJsonLdGenerator;
use Modules\Forms\Services\FormPageCacheInvalidator;
use Modules\Forms\Services\FormService;
use Modules\Forms\Services\FormSubmissionService;
use Modules\Forms\Services\FormValidationBuilder;
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
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRateLimiters();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerMenus();
        $this->registerShortcodes();
        $this->registerCommands();
        $this->registerScheduledTasks();
    }

    /**
     * Rate limiter combinado IP+form_slug para evitar bloquear a un user
     * legítimo que usa varios formularios distintos en la misma sesión.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('forms.submit', function (Request $request) {
            $slug = $request->route('slug') ?? 'unknown';
            $perFormLimit = (int) config('forms.throttle_submissions', 20);

            return [
                Limit::perMinute(5)->by($request->ip().'|'.$slug),
                Limit::perHour($perFormLimit)->by($request->ip()),
            ];
        });
    }

    /**
     * Programa los commands de mantenimiento del módulo.
     * Asume que el host corre `php artisan schedule:run` cada minuto.
     */
    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(ProcessFormFollowUpsCommand::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command(SendAbandonReminderCommand::class)
                ->hourly()
                ->withoutOverlapping();

            $schedule->command(CleanupAbandonedFormsCommand::class)
                ->dailyAt('03:15')
                ->withoutOverlapping();

            $schedule->command(CleanupFormDataCommand::class)
                ->dailyAt('03:30')
                ->withoutOverlapping();

            $schedule->command(CleanupFormTokensCommand::class)
                ->dailyAt('03:45')
                ->withoutOverlapping();

            $schedule->command(PruneFormVersionsCommand::class)
                ->weekly()
                ->sundays()
                ->at('04:00')
                ->withoutOverlapping();
        });
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
        $this->app->singleton(FormPageCacheInvalidator::class);
        $this->app->singleton(FormService::class);
        $this->app->singleton(FormValidationBuilder::class);
        $this->app->singleton(FormAnalyticsService::class);
        $this->app->singleton(FormJsonLdGenerator::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = module_path($this->moduleName, 'lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
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
                return $this->renderFormShortcode($attrs);
            }, $this->formShortcodeMeta());

            app('shortcode')->register('form-link', function (array $attrs) {
                return $this->renderFormLinkShortcode($attrs);
            }, $this->formLinkShortcodeMeta());
        });
    }

    private function renderFormShortcode(array $attrs): string
    {
        $shortcodeConfig = [
            'display' => $attrs['display'] ?? 'inline',
            'columns' => $attrs['columns'] ?? 1,
            'show_title' => filter_var($attrs['show_title'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'lazy' => filter_var($attrs['lazy'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'button_text' => $attrs['button_text'] ?? null,
            'theme' => $attrs['theme'] ?? null,
        ];

        $form = $this->resolveForm($attrs);

        if (! $form) {
            return '';
        }

        if (app()->bound('ve_preview_wrap')) {
            $html = view('forms::visual-editor.embed-preview', compact('form', 'shortcodeConfig'))->render();
            $safe = VisualEditorController::buildVeScTag('form', $attrs, '');

            return '<div data-ve-sc="'.$safe.'">'.$html.'</div>';
        }

        return view('forms::public.render', compact('form', 'shortcodeConfig'))->render();
    }

    private function renderFormLinkShortcode(array $attrs): string
    {
        $form = $this->resolveForm($attrs);

        if (! $form) {
            return '';
        }

        $text = $attrs['text'] ?? $form->submit_button_text ?? $form->name;
        $variant = $attrs['variant'] ?? 'primary';
        $size = $attrs['size'] ?? 'md';
        $icon = $attrs['icon'] ?? 'fas fa-envelope';

        $html = view('forms::public.link-trigger', [
            'form' => $form,
            'text' => $text,
            'variant' => $variant,
            'size' => $size,
            'icon' => $icon,
        ])->render();

        if (app()->bound('ve_preview_wrap')) {
            $safe = VisualEditorController::buildVeScTag('form-link', $attrs, '');

            return '<div data-ve-sc="'.$safe.'">'.$html.'</div>';
        }

        return $html;
    }

    private function resolveForm(array $attrs): ?Form
    {
        $locale = app()->getLocale();
        $slug = $attrs["slug_{$locale}"] ?? $attrs['slug'] ?? null;
        $id = ! empty($attrs['id']) ? (int) $attrs['id'] : null;

        if (! $id && ! $slug) {
            return null;
        }

        $cacheKey = $id ? "forms:active:{$id}" : "forms:active:slug:{$slug}";
        $ttl = (int) config('forms.cache.shortcode_ttl_seconds', 300);

        $callback = function () use ($id, $slug) {
            $query = Form::with('fields')->active();

            if ($id) {
                $query->where('id', $id);
            } else {
                $query->where('slug', $slug);
            }

            $form = $query->first();

            if (! $form && $slug) {
                $form = Form::with('fields')->active()->where('slug', $slug)->first();
            }

            return $form;
        };

        if (Form::cacheDriverSupportsTags()) {
            $tagId = $id ?? $callback()?->id;
            $tag = $tagId ? ['forms:shortcode', "forms:shortcode:{$tagId}"] : ['forms:shortcode'];

            return Cache::tags($tag)->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    private function formShortcodeMeta(): array
    {
        return [
            'description' => 'Inserta un formulario publicado por ID o slug. Soporta modo popup/slide y multi-idioma.',
            'example' => '[form id="1" display="inline" /]',
            'category' => 'formularios',
            'cacheable' => false,
            'attributes' => [
                'id' => ['type' => 'number', 'description' => 'ID del formulario (usar id o slug, no ambos).'],
                'slug' => ['type' => 'text', 'description' => 'Slug del formulario.'],
                'display' => [
                    'type' => 'select',
                    'description' => 'Modo de visualización.',
                    'options' => ['inline' => 'Inline', 'popup' => 'Popup', 'slide_in' => 'Slide-in'],
                    'default' => 'inline',
                ],
                'columns' => [
                    'type' => 'select',
                    'description' => 'Número de columnas del layout.',
                    'options' => ['1' => '1 columna', '2' => '2 columnas', '3' => '3 columnas'],
                    'default' => '1',
                ],
                'show_title' => [
                    'type' => 'select',
                    'description' => 'Mostrar título del formulario.',
                    'options' => ['true' => 'Sí', 'false' => 'No'],
                    'default' => 'true',
                ],
                'theme' => [
                    'type' => 'select',
                    'description' => 'Tema visual del formulario.',
                    'options' => [
                        'default' => 'Estándar',
                        'flat' => 'Plano',
                        'material' => 'Material',
                        'card' => 'Tarjetas',
                        'minimal' => 'Minimal',
                        'dark' => 'Oscuro',
                        'rounded' => 'Redondeado',
                        'bordered' => 'Con borde',
                    ],
                ],
                'lazy' => [
                    'type' => 'select',
                    'description' => 'Cargar el formulario solo cuando entre en viewport.',
                    'options' => ['true' => 'Sí (recomendado)', 'false' => 'No'],
                    'default' => 'true',
                ],
                'button_text' => ['type' => 'text', 'description' => 'Sobrescribe el texto del botón de envío.'],
            ],
        ];
    }

    private function formLinkShortcodeMeta(): array
    {
        return [
            'description' => 'Botón/enlace que abre un formulario en popup sin renderizarlo inline.',
            'example' => '[form-link id="1" text="Contactar" /]',
            'category' => 'formularios',
            'attributes' => [
                'id' => ['type' => 'number', 'description' => 'ID del formulario.'],
                'slug' => ['type' => 'text', 'description' => 'Slug del formulario.'],
                'text' => ['type' => 'text', 'description' => 'Texto del botón.'],
                'variant' => [
                    'type' => 'select',
                    'description' => 'Estilo del botón.',
                    'options' => [
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'success' => 'Success',
                        'danger' => 'Danger',
                        'outline-primary' => 'Outline primary',
                        'link' => 'Solo enlace',
                    ],
                    'default' => 'primary',
                ],
                'size' => [
                    'type' => 'select',
                    'description' => 'Tamaño del botón.',
                    'options' => ['sm' => 'Pequeño', 'md' => 'Medio', 'lg' => 'Grande'],
                    'default' => 'md',
                ],
                'icon' => ['type' => 'text', 'description' => 'Clase de Font Awesome (p.ej. "fas fa-envelope").'],
            ],
        ];
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
            'permission' => 'Forms.inbox.index|Forms.submissions.index',
        ]);

        NavService::registerSidebar('forms-inbox', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'forms.inbox.dashboard', 'permission' => 'Forms.inbox.index'],
                ['label' => 'Todas las submissions', 'route' => 'forms.inbox.index', 'permission' => 'Forms.submissions.index'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Formularios',
            'items' => [
                ['label' => 'Todos los formularios', 'route' => 'settings.forms.index', 'permission' => 'Forms.forms.index'],
                ['label' => 'Categorias', 'route' => 'settings.forms.categories.index', 'permission' => 'Forms.categories.manage'],
                ['label' => 'Biblioteca de plantillas', 'route' => 'settings.forms.templates-library.index', 'permission' => 'Forms.templates.manage'],
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
