<?php

namespace Modules\Template\Theme;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Factory;
use Modules\Template\Exceptions\UnknownLayoutFileException;

class Theme
{
    protected ?string $theme = null;

    protected string $layout = 'default';

    protected array $regions = [];

    protected array $themeConfig = [];

    protected bool $assetsLoaded = false;

    protected array $bodyAttributes = [];

    protected array $htmlAttributes = [];

    public function __construct(
        protected Dispatcher $events,
        protected Factory $view,
        protected Asset $asset
    ) {}

    /**
     * Establece el tema activo (alias de theme())
     */
    public function uses(string $theme): self
    {
        return $this->theme($theme);
    }

    /**
     * Establece el tema activo y registra sus paths
     */
    public function theme(string $theme): self
    {
        $this->theme = $theme;
        $this->themeConfig = [];
        $this->assetsLoaded = false;

        $this->asset->addPath('themes/'.$theme.'/');

        $themePath = base_path('platform/themes/'.$theme);
        if (is_dir($themePath)) {
            $this->view->addNamespace('template', $themePath);
        }

        return $this;
    }

    /**
     * Establece el layout activo
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayoutName(): string
    {
        return $this->layout;
    }

    /**
     * Obtiene el nombre del tema activo
     */
    public function getThemeName(): string
    {
        return $this->theme ?? setting('template', 'default');
    }

    /**
     * Verifica si el tema existe en el filesystem
     */
    public function exists(?string $theme): bool
    {
        if (! $theme) {
            return false;
        }

        return is_dir(base_path('platform/themes/'.$theme));
    }

    /**
     * Devuelve la instancia de Asset.
     * Dispara los assets globales del tema la primera vez que se accede.
     */
    public function asset(): Asset
    {
        $this->fireEventGlobalAssets();

        return $this->asset;
    }

    /**
     * Dispara un evento del config.php del tema
     */
    public function fire(string $event, mixed $args = null): void
    {
        $config = $this->getThemeConfig();
        $onEvent = $config['events'][$event] ?? null;

        if ($onEvent instanceof Closure) {
            $onEvent($args ?? $this);
        }

        $this->events->dispatch('theme.'.$event, $args ?? $this);
    }

    /**
     * Carga la configuración del tema activo desde config.php
     */
    protected function getThemeConfig(): array
    {
        if (! empty($this->themeConfig)) {
            return $this->themeConfig;
        }

        $configPath = base_path('platform/themes/'.$this->getThemeName().'/config.php');

        if (! File::exists($configPath)) {
            return $this->themeConfig;
        }

        try {
            $config = require $configPath;
            if (is_array($config)) {
                $this->themeConfig = $config;
            }
        } catch (\Throwable $e) {
            Log::warning('Theme config.php error for ['.$this->getThemeName().']: '.$e->getMessage());
        }

        return $this->themeConfig;
    }

    /**
     * Dispara evento global de assets (beforeRenderTheme).
     * Solo se ejecuta una vez por request.
     */
    public function fireEventGlobalAssets(): void
    {
        if ($this->assetsLoaded) {
            return;
        }

        $this->assetsLoaded = true;
        $this->fire('beforeRenderTheme', $this);
    }

    /**
     * Renderiza una vista y la almacena como contenido.
     */
    public function of(string $view, array $args = []): self
    {
        $this->fireEventGlobalAssets();
        $this->regions['content'] = $this->view->make($view, $args)->render();

        return $this;
    }

    /**
     * Renderiza una vista del tema usando namespace 'template::views.{view}'
     */
    public function scope(string $view, array $args = []): self
    {
        return $this->of('template::views.'.$view, $args);
    }

    /**
     * Renderiza un partial del tema
     */
    public function partial(string $view, array $args = []): string
    {
        $path = 'template::partials.'.$view;

        if (! $this->view->exists($path)) {
            return '';
        }

        return $this->view->make($path, $args)->render();
    }

    /**
     * Devuelve el contenido de la región content
     */
    public function content(): string
    {
        return $this->regions['content'] ?? '';
    }

    /**
     * Establece un valor en una región
     */
    public function set(string $region, mixed $value): self
    {
        $this->regions[$region] = $value;

        return $this;
    }

    /**
     * Obtiene el valor de una región
     */
    public function get(string $region, ?string $default = null): mixed
    {
        return $this->regions[$region] ?? $default ?? '';
    }

    /**
     * Alias de get() — compatibilidad con Botble Theme::place().
     */
    public function place(string $region, ?string $default = null): mixed
    {
        return $this->get($region, $default);
    }

    public function has(string $region): bool
    {
        return isset($this->regions[$region]);
    }

    public function append(string $region, string $value): self
    {
        $this->regions[$region] = ($this->regions[$region] ?? '').$value;

        return $this;
    }

    public function prepend(string $region, string $value): self
    {
        $this->regions[$region] = $value.($this->regions[$region] ?? '');

        return $this;
    }

    /**
     * Renderiza el layout del tema y devuelve el HTML completo
     */
    public function render(): string
    {
        $this->fireEventGlobalAssets();

        $layoutPath = 'template::layouts.'.$this->layout;

        if (! $this->view->exists($layoutPath)) {
            $layoutPath = 'template::layouts.default';
        }

        if (! $this->view->exists($layoutPath)) {
            throw new UnknownLayoutFileException("Layout [{$this->layout}] not found for theme [{$this->getThemeName()}].");
        }

        return $this->view->make($layoutPath, ['theme' => $this])->render();
    }

    /**
     * Devuelve HTML de los estilos del contenedor indicado
     */
    public function styles(string $container = 'default'): string
    {
        return $this->asset->container($container)->styles();
    }

    /**
     * Devuelve HTML de los scripts del contenedor indicado
     */
    public function scripts(string $container = 'footer'): string
    {
        return $this->asset->container($container)->scripts();
    }

    /**
     * Obtiene el namespace de vistas del tema activo.
     * Equivalente al namespace 'template' + el path del tema.
     * Ejemplo: 'template' (namespace registrado con addNamespace)
     */
    public function getThemeNamespace(string $path = ''): string
    {
        $namespace = 'template';

        if ($path) {
            return $namespace.'::'.$path;
        }

        return $namespace;
    }

    /**
     * Registra un composer de vistas para vistas del tema.
     * Equivalente a View::composer() pero con path del tema.
     */
    public function composer(string|array $view, Closure $callback): void
    {
        $views = (array) $view;
        $namespace = $this->getThemeNamespace('views');

        $paths = array_map(fn ($v) => $namespace.'.'.$v, $views);

        $this->view->composer($paths, $callback);
    }

    /**
     * Theme::routes() - no-op en inoqualabs.
     * Las rutas públicas están en modules/Page y otros módulos.
     */
    public function routes(): void
    {
        // Las rutas del CMS ya están definidas en los módulos correspondientes.
        // Este método existe por compatibilidad con temas diseñados para Botble.
    }

    // ─── Atributos HTML/Body ──────────────────────────────────────────────────

    public function addBodyAttributes(array $attributes): static
    {
        $this->bodyAttributes = array_merge($this->bodyAttributes, $attributes);

        return $this;
    }

    public function getBodyAttributes(): array
    {
        return $this->bodyAttributes;
    }

    public function bodyAttributes(): string
    {
        $attrs = $this->bodyAttributes;

        return implode(' ', array_map(
            fn ($k, $v) => e($k).'="'.e($v).'"',
            array_keys($attrs),
            $attrs
        ));
    }

    public function addHtmlAttributes(array $attributes): static
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $attributes);

        return $this;
    }

    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function htmlAttributes(): string
    {
        $defaults = ['lang' => str_replace('_', '-', app()->getLocale())];
        $attrs = array_merge($defaults, $this->htmlAttributes);

        return implode(' ', array_map(
            fn ($k, $v) => e($k).'="'.e($v).'"',
            array_keys($attrs),
            $attrs
        ));
    }

    // ─── Logo, favicon, título ────────────────────────────────────────────────

    public function getLogo(string $logoKey = 'logo'): ?string
    {
        $logo = theme_option($logoKey);

        return $logo ?: null;
    }

    public function getFavicon(): ?string
    {
        $favicon = theme_option('favicon');

        return $favicon ?: null;
    }

    public function getSiteTitle(): ?string
    {
        $title = theme_option('site_title', config('app.name'));

        return $title ?: null;
    }

    public function getSiteEmail(): ?string
    {
        $copyright = theme_option('email');

        return $copyright ?: null;
    }

    public function getSiteCellphone(): ?string
    {
        $copyright = theme_option('phone');

        return $copyright ?: null;
    }

    public function getSiteOpen(): ?string
    {
        $copyright = theme_option('opening_hours');

        return $copyright ?: null;
    }

    public function getSiteAddress(): ?string
    {
        $copyright = theme_option('address');

        return $copyright ?: null;
    }

    public function getSiteCopyright(): ?string
    {
        $copyright = theme_option('copyright');

        return $copyright ?: null;
    }

    // ─── Redes sociales ───────────────────────────────────────────────────────

    /**
     * Devuelve el array de redes sociales definidas en theme_option('social_links').
     * Formato: [['name'=>..., 'icon'=>..., 'url'=>..., 'color'=>...], ...]
     */
    public function getSocialLinks(): array
    {
        $raw = theme_option('social_links');
        if (! $raw) {
            return [];
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return [];
        }

        $links = [];
        foreach ($data as $item) {
            if (count($item) >= 4 && ! empty($item[2]['value'])) {
                $links[] = [
                    'name' => $item[0]['value'] ?? '',
                    'icon' => $item[1]['value'] ?? 'fa fa-link',
                    'url' => $item[2]['value'],
                    'color' => $item[3]['value'] ?? '#333',
                ];
            }
        }

        return $links;
    }

    /**
     * Devuelve el HTML del bloque <head> gestionado por el tema (estilos registrados).
     * Compatibilidad con temas diseñados para Botble.
     */
    public function header(): string
    {
        return $this->styles();
    }

    /**
     * Comparte datos con todas las vistas del tema
     */
    public function share(string $key, mixed $value): self
    {
        $this->view->share($key, $value);

        return $this;
    }

    /**
     * Obtiene la ruta física del tema activo
     */
    public function getThemePath(?string $theme = null): string
    {
        return base_path('platform/themes/'.($theme ?? $this->getThemeName()));
    }

    /**
     * Magic method para set/get/has/append/prepend sobre regiones
     */
    public function __call(string $method, array $parameters): mixed
    {
        foreach (['set', 'get', 'has', 'append', 'prepend'] as $prefix) {
            if (! str_starts_with($method, $prefix)) {
                continue;
            }

            $region = lcfirst(substr($method, strlen($prefix)));
            array_unshift($parameters, $region);

            return call_user_func_array([$this, $prefix], $parameters);
        }

        return null;
    }
}
