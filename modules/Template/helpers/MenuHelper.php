<?php

// IMPORTANTE: Este archivo NO tiene namespace para que las funciones sean globales.
// Las funciones definidas en un namespace de PHP NO son accesibles globalmente.

use Modules\Core\Models\Setting;
use Modules\Template\Helpers\BaseHelper;
use Modules\Template\Models\Menu;
use Modules\Template\Services\MenuService;
use Modules\Template\Theme\Theme;

if (! function_exists('render_menu')) {
    /**
     * Render a menu by location.
     */
    function render_menu(string $location, array $attributes = []): string
    {
        return app(MenuService::class)->renderMenu($location, $attributes);
    }
}

if (! function_exists('theme_option')) {
    /**
     * Get a theme option value.
     */
    function theme_option(string $key, string $default = ''): string
    {
        return Setting::get('theme.option.'.$key, $default) ?? $default;
    }
}

if (! function_exists('menu')) {
    /**
     * Get a menu by location.
     */
    function menu(string $location)
    {
        return Menu::where('location', $location)
            ->where('status', true)
            ->with(['items' => function ($query) {
                $query->with('children');
            }])
            ->first();
    }
}

// ─── Compatibilidad con Botble ────────────────────────────────────────────────

if (! defined('THEME_FRONT_BODY')) {
    define('THEME_FRONT_BODY', 'theme-front-body');
}

if (! defined('THEME_FRONT_HEADER')) {
    define('THEME_FRONT_HEADER', 'theme-front-header');
}

if (! defined('THEME_FRONT_FOOTER')) {
    define('THEME_FRONT_FOOTER', 'theme-front-footer');
}

if (! defined('THEME_MODULE_SCREEN_NAME')) {
    define('THEME_MODULE_SCREEN_NAME', 'theme');
}

if (! defined('THEME_OPTIONS_MODULE_SCREEN_NAME')) {
    define('THEME_OPTIONS_MODULE_SCREEN_NAME', 'theme-options');
}

if (! function_exists('apply_filters')) {
    /**
     * Aplica filtros a un valor. Stub de compatibilidad con Botble.
     * En inoqualabs no hay sistema de hooks; devuelve el valor sin modificar.
     */
    function apply_filters(string $tag, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}

if (! function_exists('is_plugin_active')) {
    /**
     * Indica si un plugin/módulo está activo.
     * 'language' está disponible en inoqualabs mediante $pageLangLinks.
     */
    function is_plugin_active(string $plugin): bool
    {
        return match ($plugin) {
            'language' => true,
            default => false,
        };
    }
}

if (! function_exists('get_layout_header_styles')) {
    /**
     * Devuelve los estilos de cabecera disponibles. Stub de compatibilidad.
     */
    function get_layout_header_styles(): array
    {
        return [];
    }
}

if (! function_exists('is_rtl')) {
    /**
     * Indica si el sitio tiene dirección RTL activada.
     */
    function is_rtl(): bool
    {
        return setting('locale_direction', 'ltr') === 'rtl';
    }
}

if (! function_exists('theme_path')) {
    /**
     * Devuelve la ruta absoluta al directorio de temas (o a un archivo dentro de él).
     */
    function theme_path(?string $path = null): string
    {
        return base_path('platform/themes'.($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (! function_exists('theme')) {
    /**
     * Acceso rápido a la instancia del Theme engine.
     */
    function theme(?string $themeName = null, ?string $layoutName = null): Theme
    {
        $theme = app(Theme::class);
        if ($themeName) {
            $theme->theme($themeName);
        }
        if ($layoutName) {
            $theme->layout($layoutName);
        }

        return $theme;
    }
}

if (! function_exists('sanitize_html_class')) {
    /**
     * Sanitiza una cadena para usarla como clase CSS.
     */
    function sanitize_html_class(string $class, string|callable|null $fallback = ''): string
    {
        $sanitized = preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $class);
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $sanitized);
        if ($sanitized === '' && $fallback) {
            return is_callable($fallback) ? (string) $fallback($class) : sanitize_html_class((string) $fallback);
        }

        return $sanitized;
    }
}

if (! function_exists('parse_args')) {
    /**
     * Fusiona argumentos con valores por defecto (similar a wp_parse_args).
     */
    function parse_args(array|object $args, string|array $defaults = ''): array
    {
        $result = is_object($args) ? get_object_vars($args) : $args;

        return is_array($defaults) ? array_merge($defaults, $result) : $result;
    }
}

// Nota: dynamic_sidebar() la provee el módulo Widget (priority 10).
// No la definimos aquí para no bloquear la implementación real.
// El tema carga functions/functions.php via app()->booted(), después de que Widget ya bootó.

if (! function_exists('do_shortcode')) {
    /**
     * Compila y renderiza shortcodes dentro de un string de contenido.
     * Equivalente a Botble's do_shortcode() — delega al módulo Shortcode.
     */
    function do_shortcode(string $content): string
    {
        if (! app()->bound('shortcode')) {
            return $content;
        }

        return app('shortcode')->compile($content);
    }
}

if (! function_exists('admin_bar')) {
    /**
     * Stub de compatibilidad con Botble.
     * Devuelve un objeto anónimo que absorbe cualquier llamada de método.
     */
    function admin_bar(): object
    {
        return new class
        {
            public function __call(string $method, array $args): static
            {
                return $this;
            }
        };
    }
}

if (! function_exists('clean')) {
    function clean(array|string|null $dirty, array|string|null $config = null): array|string|null
    {
        return app(BaseHelper::class)->clean($dirty, $config);
    }
}
