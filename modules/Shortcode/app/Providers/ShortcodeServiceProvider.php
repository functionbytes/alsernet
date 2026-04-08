<?php

namespace Modules\Shortcode\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Models\Media;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Shortcode\Console\ShortcodeClearCommand;
use Modules\Shortcode\Console\ShortcodeCompileCommand;
use Modules\Shortcode\Console\ShortcodeListCommand;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        // @shortcode directive
        Blade::directive('shortcode', function ($expression) {
            return "<?php echo shortcode($expression); ?>";
        });

        // @stripshortcodes directive
        Blade::directive('stripshortcodes', function ($expression) {
            return "<?php echo strip_shortcodes($expression); ?>";
        });
    }

    /**
     * Register default shortcodes
     */
    protected function registerShortcodes(): void
    {
        if (! config('shortcode.enabled', true)) {
            return;
        }

        $shortcode = app('shortcode');

        // Button shortcode: [button url="#" class="primary" target="_blank"]Click Me[/button]
        $shortcode->register('button', function ($attrs, $content) {
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
        $shortcode->register('alert', function ($attrs, $content) {
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
        $shortcode->register('columns', function ($attrs, $content) {
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
        $shortcode->register('column', function ($attrs, $content) {
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
        $shortcode->register('youtube', function ($attrs, $content) {
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
        $shortcode->register('image', function ($attrs, $content) {
            $id = $attrs['id'] ?? '';
            $size = $attrs['size'] ?? 'medium';
            $class = $attrs['class'] ?? 'img-fluid';
            $alt = $attrs['alt'] ?? '';

            if (empty($id)) {
                return '';
            }

            // Try to get image from Media model if exists
            try {
                if (class_exists('\Modules\Media\Models\Media')) {
                    $media = Media::find($id);
                    if ($media) {
                        $url = $media->getUrl($size);
                        $alt = $alt ?: $media->alt_text;

                        return sprintf(
                            '<img src="%s" class="%s" alt="%s" loading="lazy">',
                            htmlspecialchars($url),
                            htmlspecialchars($class),
                            htmlspecialchars($alt)
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Image shortcode error: '.$e->getMessage());
            }

            return sprintf(
                '<img src="#" class="%s" alt="%s" data-id="%s">',
                htmlspecialchars($class),
                htmlspecialchars($alt),
                htmlspecialchars($id)
            );
        }, [
            'description' => 'Inserta una imagen del gestor de medios.',
            'example' => '[image id="123" size="medium" alt="Descripción" /]',
            'attributes' => ['id' => 'ID del archivo de media', 'size' => 'Tamaño (thumbnail, medium, large)', 'class' => 'Clases CSS', 'alt' => 'Texto alternativo'],
        ]);

        // Icon shortcode: [icon name="home" size="24" color="primary" /]
        $shortcode->register('icon', function ($attrs, $content) {
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
        $shortcode->register('badge', function ($attrs, $content) {
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
        $shortcode->register('card', function ($attrs, $content) {
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
        $shortcode->register('accordion', function ($attrs, $content) {
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
        $shortcode->register('accordion-item', function ($attrs, $content) {
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
        $shortcode->register('quote', function ($attrs, $content) {
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

        // Contact Form shortcode: [contact-form title="Contacto" email="admin@site.com"][/contact-form]
        $shortcode->register('contact-form', function ($attrs, $content) {
            $title = $attrs['title'] ?? 'Contáctenos';
            $email = $attrs['email'] ?? config('mail.from.address', '');
            $formId = 'contact-form-'.uniqid();

            return sprintf(
                '<div class="contact-form-wrapper my-4">
            <h3 class="mb-3">%s</h3>
            <form id="%s" class="contact-shortcode-form" data-email="%s">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required placeholder="Tu nombre">
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="tu@email.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="Tu mensaje..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar mensaje</button>
                <div class="contact-form-feedback mt-2"></div>
            </form>
        </div>',
                htmlspecialchars($title),
                htmlspecialchars($formId),
                htmlspecialchars($email)
            );
        }, [
            'description' => 'Formulario de contacto con envío por email.',
            'example' => '[contact-form title="Contáctenos" email="info@empresa.com"][/contact-form]',
            'attributes' => ['title' => 'Título del formulario', 'email' => 'Dirección de email destino'],
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
