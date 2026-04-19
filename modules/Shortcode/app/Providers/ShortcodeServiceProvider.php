<?php

namespace Modules\Shortcode\Providers;

use App\Services\NavService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Models\Media;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Shortcode\Console\ShortcodeBenchmarkCommand;
use Modules\Shortcode\Console\ShortcodeClearCommand;
use Modules\Shortcode\Console\ShortcodeCompileCommand;
use Modules\Shortcode\Console\ShortcodeFindCommand;
use Modules\Shortcode\Console\ShortcodeListCommand;
use Modules\Shortcode\Console\ShortcodeMakeCommand;
use Modules\Shortcode\Console\ShortcodePreviewCommand;
use Modules\Shortcode\Console\ShortcodeValidateCommand;
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
        $this->registerCommandSchedules();
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
                ['label' => 'Listado', 'route' => 'setting.shortcode.index', 'permission' => 'shortcode.view'],
                ['label' => 'Referencia', 'route' => 'setting.shortcode.reference', 'permission' => 'shortcode.view'],
                ['label' => 'Tester visual', 'route' => 'setting.shortcode.tester', 'permission' => 'shortcode.view'],
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
     * Register default shortcodes
     */
    protected function registerShortcodes(): void
    {
        if (! config('shortcode.enabled', true) || ! config('shortcode.auto_register', true)) {
            return;
        }

        $shortcode = app('shortcode');
        $enabled = config('shortcode.default_shortcodes', []);

        // Closure helper: registra sólo si la config permite el shortcode.
        $register = function (string $name, callable $callback, array $meta = []) use ($shortcode, $enabled) {
            if (($enabled[$name] ?? true) === false) {
                return;
            }
            $shortcode->register($name, $callback, $meta);
        };

        // Button shortcode: [button url="#" class="primary" target="_blank"]Click Me[/button]
        $register('button', function ($attrs, $content) {
            $class = $attrs['class'] ?? 'btn-primary';
            $url = $attrs['url'] ?? '#';
            $target = isset($attrs['target']) ? sprintf(' target="%s"', $attrs['target']) : '';
            $id = isset($attrs['id']) ? sprintf(' id="%s"', $attrs['id']) : '';

            return sprintf(
                '<a href="%s" class="btn %s"%s%s>%s</a>',
                htmlspecialchars($url),
                htmlspecialchars($class),
                $target,
                $id,
                $content
            );
        }, [
            'description' => 'Inserta un botón con enlace.',
            'example' => '[button url="#" class="btn-primary" target="_blank"]Haz clic[/button]',
            'attributes' => ['url' => 'URL del enlace', 'class' => 'Clases CSS del botón', 'target' => 'Destino (_blank, _self)', 'id' => 'ID del elemento'],
        ]);

        // Alert shortcode: [alert type="success" dismissible="true"]Message[/alert]
        $register('alert', function ($attrs, $content) {
            $type = $attrs['type'] ?? 'info';
            $dismissible = isset($attrs['dismissible']) && $attrs['dismissible'] === 'true';
            $dismissButton = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
            $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';

            return sprintf(
                '<div class="alert alert-%s%s" role="alert">%s%s</div>',
                htmlspecialchars($type),
                $dismissClass,
                $content,
                $dismissButton
            );
        }, [
            'description' => 'Muestra un mensaje de alerta con estilo Bootstrap.',
            'example' => '[alert type="success" dismissible="true"]Operación exitosa[/alert]',
            'attributes' => ['type' => 'Tipo de alerta (success, danger, warning, info)', 'dismissible' => 'Mostrar botón cerrar (true/false)'],
        ]);

        // Columns shortcode: [columns count="3" gap="4"]Content[/columns]
        $register('columns', function ($attrs, $content) {
            $count = $attrs['count'] ?? 2;
            $gap = $attrs['gap'] ?? 3;

            return sprintf(
                '<div class="row row-cols-1 row-cols-md-%d g-%d">%s</div>',
                (int) $count,
                (int) $gap,
                $content
            );
        }, [
            'description' => 'Crea una cuadrícula de columnas Bootstrap.',
            'example' => '[columns count="3" gap="4"][column]Col 1[/column][column]Col 2[/column][/columns]',
            'attributes' => ['count' => 'Número de columnas (1–12)', 'gap' => 'Espacio entre columnas (0–5)'],
        ]);

        // Column shortcode: [column]Content[/column]
        $register('column', function ($attrs, $content) {
            $class = $attrs['class'] ?? '';

            return sprintf(
                '<div class="col %s">%s</div>',
                htmlspecialchars($class),
                $content
            );
        }, [
            'description' => 'Columna individual dentro de un shortcode [columns].',
            'example' => '[column class="col-md-6"]Contenido[/column]',
            'attributes' => ['class' => 'Clases CSS adicionales'],
        ]);

        // YouTube shortcode: [youtube id="abc123" width="560" height="315" /]
        $register('youtube', function ($attrs, $content) {
            $id = $attrs['id'] ?? '';
            $width = $attrs['width'] ?? '560';
            $height = $attrs['height'] ?? '315';
            $title = $attrs['title'] ?? 'YouTube video player';

            if (empty($id)) {
                return '';
            }

            return sprintf(
                '<div class="ratio ratio-16x9"><iframe width="%s" height="%s" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
                htmlspecialchars($width),
                htmlspecialchars($height),
                htmlspecialchars($id),
                htmlspecialchars($title)
            );
        }, [
            'description' => 'Incrusta un video de YouTube con aspecto 16:9.',
            'example' => '[youtube id="dQw4w9WgXcQ" /]',
            'attributes' => ['id' => 'ID del video de YouTube', 'title' => 'Título descriptivo del video'],
        ]);

        // Image shortcode: [image id="123" size="medium" class="img-fluid" alt="Description" /]
        $register('image', function ($attrs, $content) {
            $id = $attrs['id'] ?? '';
            $size = $attrs['size'] ?? 'medium';
            $class = $attrs['class'] ?? 'img-fluid';
            $alt = $attrs['alt'] ?? '';

            if (! ctype_digit((string) $id) || (int) $id <= 0) {
                return '';
            }

            try {
                if (class_exists(Media::class)) {
                    $media = Media::find((int) $id);
                    if ($media) {
                        $url = $media->getUrl($size);
                        $alt = $alt ?: $media->alt_text;

                        return sprintf(
                            '<img src="%s" class="%s" alt="%s" loading="lazy">',
                            htmlspecialchars($url),
                            htmlspecialchars($class),
                            htmlspecialchars((string) $alt)
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Image shortcode error: '.$e->getMessage(), ['id' => $id]);
            }

            return sprintf(
                '<img src="#" class="%s" alt="%s" data-id="%s">',
                htmlspecialchars($class),
                htmlspecialchars((string) $alt),
                htmlspecialchars((string) $id)
            );
        }, [
            'description' => 'Inserta una imagen del gestor de medios.',
            'example' => '[image id="123" size="medium" alt="Descripción" /]',
            'attributes' => ['id' => 'ID del archivo de media', 'size' => 'Tamaño (thumbnail, medium, large)', 'class' => 'Clases CSS', 'alt' => 'Texto alternativo'],
        ]);

        // Icon shortcode: [icon name="home" size="24" color="primary" /]
        $register('icon', function ($attrs, $content) {
            $name = $attrs['name'] ?? 'circle';
            $size = $attrs['size'] ?? '24';
            $color = $attrs['color'] ?? '';
            $class = $attrs['class'] ?? '';

            $colorClass = $color ? "text-{$color}" : '';

            return sprintf(
                '<i class="bi bi-%s %s %s" style="font-size: %spx;"></i>',
                htmlspecialchars($name),
                htmlspecialchars($colorClass),
                htmlspecialchars($class),
                htmlspecialchars($size)
            );
        }, [
            'description' => 'Inserta un icono Bootstrap Icons.',
            'example' => '[icon name="star" size="24" color="warning" /]',
            'attributes' => ['name' => 'Nombre del icono Bootstrap', 'size' => 'Tamaño en píxeles', 'color' => 'Color Bootstrap (primary, success...)', 'class' => 'Clases CSS adicionales'],
        ]);

        // Badge shortcode: [badge type="primary"]New[/badge]
        $register('badge', function ($attrs, $content) {
            $type = $attrs['type'] ?? 'primary';
            $pill = isset($attrs['pill']) && $attrs['pill'] === 'true' ? ' rounded-pill' : '';

            return sprintf(
                '<span class="badge bg-%s%s">%s</span>',
                htmlspecialchars($type),
                $pill,
                $content
            );
        }, [
            'description' => 'Muestra una etiqueta (badge) con color Bootstrap.',
            'example' => '[badge type="success" pill="true"]Nuevo[/badge]',
            'attributes' => ['type' => 'Color Bootstrap (primary, success, danger...)', 'pill' => 'Bordes redondeados (true/false)'],
        ]);

        // Card shortcode: [card title="Title" class="mb-3"]Content[/card]
        $register('card', function ($attrs, $content) {
            $title = $attrs['title'] ?? '';
            $class = $attrs['class'] ?? '';
            $headerClass = $attrs['header_class'] ?? '';

            $titleHtml = $title ? sprintf('<div class="card-header %s"><h5 class="card-title mb-0">%s</h5></div>', htmlspecialchars($headerClass), htmlspecialchars($title)) : '';

            return sprintf(
                '<div class="card %s">%s<div class="card-body">%s</div></div>',
                htmlspecialchars($class),
                $titleHtml,
                $content
            );
        }, [
            'description' => 'Crea una tarjeta Bootstrap con cabecera opcional.',
            'example' => '[card title="Mi tarjeta" class="mb-3"]Contenido de la tarjeta[/card]',
            'attributes' => ['title' => 'Título de la cabecera', 'class' => 'Clases CSS adicionales', 'header_class' => 'Clases de la cabecera'],
        ]);

        // Accordion shortcode: [accordion id="acc1"]Content[/accordion]
        $register('accordion', function ($attrs, $content) {
            $id = $attrs['id'] ?? 'accordion-'.uniqid();
            $class = $attrs['class'] ?? '';

            return sprintf(
                '<div class="accordion %s" id="%s">%s</div>',
                htmlspecialchars($class),
                htmlspecialchars($id),
                $content
            );
        }, [
            'description' => 'Contenedor de acordeón Bootstrap.',
            'example' => '[accordion id="faq"][accordion-item title="Pregunta 1" parent="faq"]Respuesta[/accordion-item][/accordion]',
            'attributes' => ['id' => 'ID único del acordeón', 'class' => 'Clases CSS adicionales'],
        ]);

        // Accordion Item shortcode: [accordion-item title="Item 1" parent="acc1"]Content[/accordion-item]
        $register('accordion-item', function ($attrs, $content) {
            $title = $attrs['title'] ?? 'Accordion Item';
            $parent = $attrs['parent'] ?? 'accordion';
            $id = $attrs['id'] ?? 'item-'.uniqid();
            $show = isset($attrs['show']) && $attrs['show'] === 'true' ? ' show' : '';
            $expanded = isset($attrs['show']) && $attrs['show'] === 'true' ? 'true' : 'false';
            $collapsed = $show ? '' : ' collapsed';

            return sprintf(
                '<div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button%s" type="button" data-bs-toggle="collapse" data-bs-target="#%s" aria-expanded="%s">
                            %s
                        </button>
                    </h2>
                    <div id="%s" class="accordion-collapse collapse%s" data-bs-parent="#%s">
                        <div class="accordion-body">%s</div>
                    </div>
                </div>',
                $collapsed,
                htmlspecialchars($id),
                $expanded,
                htmlspecialchars($title),
                htmlspecialchars($id),
                $show,
                htmlspecialchars($parent),
                $content
            );
        }, [
            'description' => 'Elemento individual de acordeón.',
            'example' => '[accordion-item title="¿Cómo funciona?" parent="faq" id="item1"]Descripción aquí[/accordion-item]',
            'attributes' => ['title' => 'Texto del encabezado', 'parent' => 'ID del acordeón padre', 'id' => 'ID único del ítem', 'show' => 'Abierto por defecto (true/false)'],
        ]);

        // Quote shortcode: [quote author="John Doe" cite="Book Title"]Quote text[/quote]
        $register('quote', function ($attrs, $content) {
            $author = $attrs['author'] ?? '';
            $cite = $attrs['cite'] ?? '';

            $footer = '';
            if ($author || $cite) {
                $authorHtml = $author ? htmlspecialchars($author) : '';
                $citeHtml = $cite ? sprintf('<cite title="%s">%s</cite>', htmlspecialchars($cite), htmlspecialchars($cite)) : '';
                $footer = sprintf('<footer class="blockquote-footer">%s %s</footer>', $authorHtml, $citeHtml);
            }

            return sprintf(
                '<blockquote class="blockquote"><p>%s</p>%s</blockquote>',
                $content,
                $footer
            );
        }, [
            'description' => 'Blockquote con autor y fuente opcionales.',
            'example' => '[quote author="Ada Lovelace" cite="Notas, 1843"]El motor analítico teje patrones algebraicos.[/quote]',
            'attributes' => ['author' => 'Nombre del autor', 'cite' => 'Fuente o título de la obra'],
        ]);

        // Contact Form shortcode (DEMO): HTML-only, sin submit backend.
        // Para formularios funcionales usa el módulo Forms: [form id="X"].
        $register('contact-form', function ($attrs, $content) {
            $title = $attrs['title'] ?? 'Contáctenos';
            $formId = 'contact-form-'.uniqid();

            return sprintf(
                '<div class="contact-form-wrapper my-4 border rounded p-3">
            <div class="small text-muted mb-2"><i class="fas fa-circle-info me-1"></i>Demo sin envío real — usa [form id="X"] para formularios funcionales</div>
            <h3 class="mb-3">%s</h3>
            <form id="%s" class="contact-shortcode-form" onsubmit="return false;" aria-disabled="true">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" placeholder="Tu nombre" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="tu@email.com" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Tu mensaje..." disabled></textarea>
                </div>
                <button type="button" class="btn btn-secondary" disabled>Vista previa (no envía)</button>
            </form>
        </div>',
                htmlspecialchars($title),
                htmlspecialchars($formId)
            );
        }, [
            'description' => 'Formulario de contacto (solo maquetado, sin envío). Usa [form id="X"] del módulo Forms para funcionalidad real.',
            'example' => '[contact-form title="Contáctenos"][/contact-form]',
            'attributes' => ['title' => 'Título del formulario'],
        ]);
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
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
