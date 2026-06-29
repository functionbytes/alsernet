<?php

namespace Modules\Shortcode\Providers;

use App\Services\NavService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Shortcode\Console\ShortcodeBenchmarkCommand;
use Modules\Shortcode\Console\ShortcodeClearCommand;
use Modules\Shortcode\Console\ShortcodeCompileCommand;
use Modules\Shortcode\Console\ShortcodeFindCommand;
use Modules\Shortcode\Console\ShortcodeListCommand;
use Modules\Shortcode\Console\ShortcodeMakeCommand;
use Modules\Shortcode\Console\ShortcodePreviewCommand;
use Modules\Shortcode\Console\ShortcodeValidateCommand;
use Modules\Shortcode\Shortcodes\ContentShortcodes;
use Modules\Shortcode\Shortcodes\IntegrationShortcodes;
use Modules\Shortcode\Shortcodes\LogicShortcodes;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * SEGURIDAD — Política de escape en shortcodes.
 *
 * - Los *atributos* (url, class, type, id, etc.) se escapan con htmlspecialchars().
 * - El *contenido* entre tags ([alert]...[/alert]) se inserta SIN escape, porque
 *   admite HTML y shortcodes anidados (comportamiento estilo WordPress).
 *
 * Por tanto los shortcodes sólo deben procesarse sobre contenido CONFIABLE de
 * administradores. Nunca pasar input de usuario anónimo a Shortcode::compile()
 * sin antes sanitizar con HTMLPurifier o strip_tags().
 */
class ShortcodeServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Shortcode';

    protected string $nameLower = 'shortcode';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if (Module::find('Shortcode')?->isDisabled()) {
            return;
        }

        $this->registerCommands();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerHelpers();
        $this->registerBladeDirectives();
        $this->registerShortcodes();
        $this->registerCompanionShortcodes();
        $this->registerRateLimiters();
        $this->registerMenus();
    }

    /**
     * Registra la entrada del módulo en la navegación de Settings.
     */
    protected function registerMenus(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Shortcodes',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'settings.shortcode.dashboard', 'permission' => 'shortcode.view'],
                ['label' => 'Referencia', 'route' => 'settings.shortcode.reference', 'permission' => 'shortcode.view'],
                ['label' => 'Tester visual', 'route' => 'settings.shortcode.tester', 'permission' => 'shortcode.view'],
            ],
        ]);
    }

    /**
     * Define un limitador personalizado para el API de shortcodes: si el
     * usuario está autenticado, cuenta por user-id; si no, por IP.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('shortcode-api', function ($request) {
            $key = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(120)->by('shortcode-api:'.$key);
        });
    }

    /**
     * Registra shortcodes de integración (Forms, Page, Menu, Blog, Media) y
     * lógicos/contextuales ([if], [for], [user-name], etc.).
     */
    protected function registerCompanionShortcodes(): void
    {
        if (! config('shortcode.enabled', true) || ! config('shortcode.auto_register', true)) {
            return;
        }

        $compiler = app('shortcode');

        (new IntegrationShortcodes($compiler))->registerAll();
        (new LogicShortcodes($compiler))->registerAll();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register the ShortcodeCompiler as singleton
        $this->app->singleton('shortcode', function ($app) {
            return new ShortcodeCompiler;
        });
    }

    /**
     * Register helpers
     */
    protected function registerHelpers(): void
    {
        $helperFile = module_path($this->name, 'helpers/shortcode.php');

        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @shortcode('contenido') — compila shortcodes en el string dado.
        Blade::directive('shortcode', function ($expression) {
            return "<?php echo shortcode($expression); ?>";
        });

        // @stripshortcodes('contenido') — elimina shortcodes del string dado.
        Blade::directive('stripshortcodes', function ($expression) {
            return "<?php echo strip_shortcodes($expression); ?>";
        });

        // @shortcodePicker('#targetSelector') — incluye el modal picker.
        Blade::directive('shortcodePicker', function ($expression) {
            $expression = trim($expression) !== '' ? $expression : "'#content'";

            return "<?php echo \$__env->make('shortcode::components.picker', ['targetSelector' => $expression], \\Illuminate\\Support\\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
        });
    }

    /**
     * Register default content shortcodes (button, alert, accordion, etc.).
     */
    protected function registerShortcodes(): void
    {
        if (! config('shortcode.enabled', true) || ! config('shortcode.auto_register', true)) {
            return;
        }

        (new ContentShortcodes(app('shortcode')))->registerAll();
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            ShortcodeListCommand::class,
            ShortcodeClearCommand::class,
            ShortcodeCompileCommand::class,
            ShortcodePreviewCommand::class,
            ShortcodeFindCommand::class,
            ShortcodeMakeCommand::class,
            ShortcodeBenchmarkCommand::class,
            ShortcodeValidateCommand::class,
        ]);
    }

    /**
     * Register translations.
     */
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

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
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

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
