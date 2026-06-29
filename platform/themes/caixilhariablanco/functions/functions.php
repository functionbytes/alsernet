<?php

use Modules\Page\Http\Controllers\VisualEditorController;

/**
 * Funciones Generales - Plantilla Wowy
 *
 * Nota: Wowy fue diseñado para Botble.
 * Aquí incluimos solo las funciones compatibles con inoqualab
 */

// Dummy helper functions para compatibilidad
if (! function_exists('register_page_template')) {
    function register_page_template($templates)
    {
        return true;
    }
}

if (! function_exists('get_application_currency')) {
    function get_application_currency()
    {
        return (object) [
            'symbol' => '$',
            'title' => 'USD',
            'decimals' => 2,
            'is_prefix_symbol' => true,
        ];
    }
}

if (! function_exists('get_ecommerce_setting')) {
    function get_ecommerce_setting($key, $default = null)
    {
        return $default;
    }
}

if (! function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        $theme = setting('template', 'wowy');

        return url('themes/'.$theme.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $theme = setting('template', 'wowy');

        return asset('themes/'.$theme.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_image')) {
    function theme_image(string $path): string
    {
        return theme_asset('images/'.ltrim($path, '/'));
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return in_array(app()->getLocale(), config('template.rtl_languages', ['ar', 'he']));
    }
}

if (! function_exists('rtl_class')) {
    function rtl_class(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

if (! function_exists('get_text_direction')) {
    function get_text_direction(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

if (! function_exists('theme_trans')) {
    function theme_trans(string $key, array $replace = []): string
    {
        return __($key);
    }
}

if (! function_exists('theme_option')) {
    /**
     * Lee una opción del tema desde la tabla settings.
     * Las opciones se guardan con prefijo 'theme.option.'
     */
    function theme_option(string $key, mixed $default = null): mixed
    {
        return setting('theme.option.'.$key, $default);
    }
}

if (! function_exists('is_plugin_active')) {
    /**
     * Verifica si un módulo/plugin está activo comprobando si su Service Provider está cargado.
     */
    function is_plugin_active(string $plugin): bool
    {
        $moduleMap = [
            'ecommerce' => 'Modules\\Ecommerce\\Providers\\EcommerceServiceProvider',
            'blog' => 'Modules\\Blog\\Providers\\BlogServiceProvider',
            'language' => 'Modules\\Language\\Providers\\LanguageServiceProvider',
            'ads' => 'Modules\\Ads\\Providers\\AdsServiceProvider',
            'simple-slider' => 'Modules\\Slider\\Providers\\SliderServiceProvider',
            'newsletter' => 'Modules\\Newsletter\\Providers\\NewsletterServiceProvider',
            'contact' => 'Modules\\Contact\\Providers\\ContactServiceProvider',
        ];

        $provider = $moduleMap[$plugin] ?? null;
        if (! $provider) {
            return false;
        }

        return array_key_exists($provider, app()->getLoadedProviders());
    }
}

if (! function_exists('get_time_to_read')) {
    /**
     * Estima el tiempo de lectura de un post en minutos.
     */
    function get_time_to_read($post, int $wordsPerMinute = 200): int
    {
        $content = strip_tags($post->content ?? '');
        $wordCount = str_word_count($content);

        return max(1, (int) ceil($wordCount / $wordsPerMinute));
    }
}

if (! function_exists('get_related_posts')) {
    /**
     * Obtiene posts relacionados del módulo Blog.
     */
    function get_related_posts(int $postId, int $limit = 4)
    {
        $blogPostClass = 'Modules\\Blog\\Models\\Post';
        if (! class_exists($blogPostClass)) {
            return collect();
        }

        return $blogPostClass::query()
            ->published()
            ->where('id', '!=', $postId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}

if (! function_exists('get_blog_single_layouts')) {
    /**
     * Retorna los layouts disponibles para el post individual del blog.
     */
    function get_blog_single_layouts(): array
    {
        return [
            'blog-right-sidebar' => __('Right sidebar'),
            'blog-left-sidebar' => __('Left sidebar'),
            'blog-full-width' => __('Full width'),
        ];
    }
}

if (! function_exists('theme_get_autoplay_speed_options')) {
    /**
     * Retorna las opciones de velocidad de autoplay para sliders.
     */
    function theme_get_autoplay_speed_options(): array
    {
        return [1000, 1500, 2000, 3000, 4000, 5000, 6000, 8000];
    }
}

if (! function_exists('get_currencies_json')) {
    /**
     * Retorna las monedas en formato JSON-serializable.
     */
    function get_currencies_json(): array
    {
        return [];
    }
}

if (! function_exists('format_price')) {
    /**
     * Formatea un precio con el símbolo de la moneda activa.
     */
    function format_price(float $price, ?object $currency = null): string
    {
        $currency = $currency ?? get_application_currency();
        $formatted = number_format($price, $currency->decimals ?? 2);

        return $currency->is_prefix_symbol
            ? ($currency->symbol ?? '$').$formatted
            : $formatted.($currency->symbol ?? '$');
    }
}

if (! function_exists('add_shortcode')) {
    /**
     * Registra un shortcode en el compilador del módulo Shortcode.
     * Compatible con la firma de Botble: add_shortcode(key, name, description, callback).
     */
    function add_shortcode(string $key, string $name, string $description, callable $callback): void
    {
        if (! app()->bound('shortcode')) {
            return;
        }

        // Wrap the callback so the visual-editor preview can inject a data-ve-sc sentinel
        // div around the output, allowing extractContent() to restore the raw tag on save.
        app('shortcode')->register($key, function (array $attrs, string $content) use ($key, $callback): string {
            try {
                $html = (string) call_user_func($callback, (object) $attrs, $content);
            } catch (Exception $e) {
                $html = '';
            }

            if (app()->bound('ve_preview_wrap')) {
                $safe = VisualEditorController::buildVeScTag($key, $attrs, $content);

                return '<div data-ve-sc="'.$safe.'">'.$html.'</div>';
            }

            return $html;
        });
    }
}

if (! function_exists('getSiteTitle')) {
    function getSiteTitle(): string
    {
        return config('app.name', 'Inoqualabs');
    }
}
