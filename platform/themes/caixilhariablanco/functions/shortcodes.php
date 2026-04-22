<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Modules\Template\Facades\Theme;

/**
 * Shortcodes del tema Wowy
 *
 * Los shortcodes genéricos se definen aquí.
 * Los shortcodes con render_template en BD se registran con add_db_shortcode().
 */

/**
 * Registra un shortcode cuyo template HTML está almacenado en la tabla `shortcodes`.
 * El campo `render_template` se compila con Blade::render($template, $attrs).
 */
function add_db_shortcode(string $key): void
{
    if (! function_exists('add_shortcode')) {
        return;
    }

    add_shortcode($key, $key, $key, function ($shortcode) use ($key) {
        $row = DB::table('shortcodes')->where('key', $key)->first();
        if (! $row || empty($row->render_template)) {
            return '';
        }
        $attrs = is_object($shortcode) ? (array) $shortcode : (array) ($shortcode ?? []);
        try {
            $html = Blade::render($row->render_template, $attrs);
        } catch (Throwable) {
            $html = $row->render_template;
        }

        // Procesar shortcodes anidados en el template (ej: [form] dentro del hero)
        return function_exists('shortcode') ? shortcode($html) : $html;
    });
}

/**
 * Registra un shortcode parametrizado que carga su template desde la tabla `shortcodes`
 * usando la clave compuesta: "{base}-{service}" (ej: service-content-ventanas).
 */
function add_db_shortcode_keyed(string $shortcodeName, string $keyPrefix): void
{
    if (! function_exists('add_shortcode')) {
        return;
    }

    add_shortcode($shortcodeName, $shortcodeName, $shortcodeName, function ($shortcode) use ($keyPrefix) {
        $service = is_object($shortcode) ? ($shortcode->service ?? '') : ($shortcode['service'] ?? '');
        if (! $service) {
            return '';
        }
        $row = DB::table('shortcodes')->where('key', "{$keyPrefix}-{$service}")->first();
        if (! $row || empty($row->render_template)) {
            return '';
        }
        try {
            $html = Blade::render($row->render_template, []);
        } catch (Throwable) {
            $html = $row->render_template;
        }

        return function_exists('shortcode') ? shortcode($html) : $html;
    });
}

app()->booted(function (): void {

    /**
     * Shortcode: our-offices
     * Muestra las cajas de información de contacto desde Theme Options.
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('our-offices', __('Our offices'), __('Our offices'), function () {
            return Theme::partial('shortcodes.our-offices');
        });
    }

    /**
     * Shortcode: cta-floating
     * Barra flotante con botones de WhatsApp y llamada, fijada en la esquina inferior.
     * Usa theme_option('phone') como fuente y labels traducibles por idioma.
     * Uso: [cta-floating /]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('cta-floating', __('Floating CTA'), __('Botones flotantes de WhatsApp y llamada'), function () {
            return Theme::partial('shortcodes.cta-floating');
        });
    }

    /**
     * Shortcode: site-features
     * Muestra características del sitio (íconos + textos).
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('site-features', __('Site features'), __('Site features'), function ($shortcode) {
            return Theme::partial('shortcodes.site-features', compact('shortcode'));
        });
    }

    /**
     * Shortcode: reviews
     * Muestra tarjetas de reseñas Google aprobadas.
     * Atributos: limit (default 6), show_rating (default true)
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('reviews', __('Google reviews'), __('Google reviews'), function ($shortcode) {
            return Theme::partial('shortcodes.reviews', [
                'limit' => $shortcode->limit ?? 6,
                'show_rating' => $shortcode->show_rating ?? true,
                'reviews_section_title' => $shortcode->title ?? null,
            ]);
        });
    }

    /**
     * Shortcode: gallery
     * Muestra galería de imágenes desde URLs separadas por coma.
     * Uso: [gallery images="url1,url2,url3"] [gallery images="..." columns="4"] [gallery images="..." lightbox="false"]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('gallery', __('Image gallery'), __('Image gallery'), function ($shortcode) {
            $images = [];

            if (! empty($shortcode->images)) {
                $images = array_filter(array_map('trim', explode(',', $shortcode->images)));
            }

            $columns = (int) ($shortcode->columns ?? 3);
            $lightbox = ($shortcode->lightbox ?? 'true') !== 'false';

            return view(Theme::getThemeNamespace().'::partials.shortcodes.gallery', compact('images', 'columns', 'lightbox'))->render();
        });
    }

    /**
     * Shortcode: projects
     * Galería de instalaciones con items definidos inline.
     *
     * Uso:
     *   [projects title="Galería de instalaciones" columns="3"]
     *   https://img.jpg | Título del proyecto | Lisboa
     *   https://img2.jpg | Otro proyecto | Porto
     *   [/projects]
     *
     * Cada línea: imagen_url | título | ubicación (título y ubicación opcionales)
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('projects', __('Galería de proyectos'), __('Galería de instalaciones'), function ($shortcode, string $content = '') {
            $columns = (int) ($shortcode->columns ?? 3);
            $title = $shortcode->title ?? null;
            $content = trim($content);
            $items = [];

            // Formato estructurado: [item image="..." title="..." location="..." /]
            if (str_contains($content, '[item')) {
                preg_match_all('/\[item([^\]]*?)\/?\]/', $content, $matches);
                foreach ($matches[1] as $attrStr) {
                    preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attrStr, $pairs, PREG_SET_ORDER);
                    $attr = [];
                    foreach ($pairs as $p) {
                        $attr[$p[1]] = $p[2];
                    }
                    if (! empty($attr['image'])) {
                        $items[] = [
                            'image' => $attr['image'],
                            'title' => $attr['title'] ?? '',
                            'location' => $attr['location'] ?? '',
                        ];
                    }
                }
            }

            // Formato líneas: https://... | Título | Ubicación
            if (empty($items)) {
                foreach (array_filter(array_map('trim', explode("\n", $content))) as $line) {
                    if (str_starts_with($line, '[')) {
                        continue;
                    }
                    $parts = array_map('trim', explode('|', $line));
                    $url = $parts[0] ?? null;
                    if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
                        continue;
                    }
                    $items[] = [
                        'image' => $url,
                        'title' => $parts[1] ?? '',
                        'location' => $parts[2] ?? '',
                    ];
                }
            }

            if (empty($items)) {
                return '';
            }

            return view(Theme::getThemeNamespace().'::partials.shortcodes.projects', compact('items', 'columns', 'title'))->render();
        });
    }

    /**
     * Shortcode: reviews-service
     * Reseñas Google aleatorias con filtro por tag y cantidad configurable.
     * Uso: [reviews-service limit="6" tag="ventanas" title="..." subtitle="..."]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('reviews-service', __('Reseñas de servicio'), __('Reseñas Google aleatorias filtradas por tag'), function ($shortcode) {
            return Theme::partial('shortcodes.reviews-service', [
                'limit' => $shortcode->limit ?? 6,
                'tag' => $shortcode->tag ?? null,
                'title' => $shortcode->title ?? null,
                'subtitle' => $shortcode->subtitle ?? null,
            ]);
        });
    }

    /**
     * Shortcode: faq-section
     * Sección FAQ con acordeón Bootstrap y header lateral con CTA.
     *
     * Uso:
     *   [faq-section title="..." subtitle="..." cta_text="..." cta_url="tel:+351..."]
     *   [item question="¿Pregunta?" answer="Respuesta." /]
     *   [item question="¿Otra?" answer="Otra respuesta." /]
     *   [/faq-section]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('faq-section', __('Sección FAQ'), __('Acordeón de preguntas frecuentes con items inline'), function ($shortcode, string $content = '') {
            $items = [];

            if (str_contains($content, '[item')) {
                preg_match_all('/\[item([^\]]*?)\/?\]/', $content, $matches);
                foreach ($matches[1] as $attrStr) {
                    preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attrStr, $pairs, PREG_SET_ORDER);
                    $attr = [];
                    foreach ($pairs as $p) {
                        $attr[$p[1]] = $p[2];
                    }
                    if (! empty($attr['question'])) {
                        $items[] = [
                            'question' => $attr['question'],
                            'answer' => $attr['answer'] ?? '',
                        ];
                    }
                }
            }

            return Theme::partial('shortcodes.faq-section', [
                'items' => $items,
                'title' => $shortcode->title ?? null,
                'subtitle' => $shortcode->subtitle ?? null,
                'cta_text' => $shortcode->cta_text ?? null,
                'cta_url' => $shortcode->cta_url ?? null,
            ]);
        });
    }

    /**
     * Shortcode: installation-gallery
     * Galería de imágenes solo-imagen con lightbox Magnific Popup.
     *
     * Uso:
     *   [installation-gallery]
     *   [img src="/pages/images/foto1.jpg" /]
     *   [img src="/pages/images/foto2.jpg" /]
     *   [/installation-gallery]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('installation-gallery', __('Installation Gallery'), __('Cuadrícula de fotos con lightbox, encabezado de sección opcional'), function ($shortcode, string $content = '') {
            $images = [];

            if (str_contains((string) $content, '[img')) {
                preg_match_all('/\[img([^\]]*?)\/?\]/', (string) $content, $matches);
                foreach ($matches[1] as $attrStr) {
                    preg_match('/src=["\']([^"\']+)["\']/', $attrStr, $m);
                    if (! empty($m[1])) {
                        $images[] = $m[1];
                    }
                }
            }

            $title = is_object($shortcode) ? ($shortcode->title ?? null) : ($shortcode['title'] ?? null);
            $subtitle = is_object($shortcode) ? ($shortcode->subtitle ?? null) : ($shortcode['subtitle'] ?? null);

            return Theme::partial('shortcodes.installation-gallery', compact('images', 'title', 'subtitle'));
        });
    }

    /**
     * Shortcode: hero-slider
     * Carrusel hero con slides configurables.
     *
     * Uso:
     *   [hero-slider]
     *   [slide image="/img.png" badge="..." title="..." text="..."
     *          btn1_text="..." btn1_url="/ruta"
     *          btn2_text="..." btn2_url="/ruta"
     *          stat="1.000+" stat_label="clientes" /]
     *   [/hero-slider]
     */
    if (function_exists('add_shortcode')) {
        add_shortcode('hero-slider', __('Hero slider'), __('Carrusel de slides hero con imagen, título, descripción y botones'), function ($shortcode, string $content = '') {
            $slides = [];

            if (str_contains((string) $content, '[slide')) {
                preg_match_all('/\[slide([^\]]*?)\/?\]/', (string) $content, $matches);
                foreach ($matches[1] as $attrStr) {
                    preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attrStr, $pairs, PREG_SET_ORDER);
                    $attr = [];
                    foreach ($pairs as $p) {
                        $attr[$p[1]] = $p[2];
                    }
                    if (! empty($attr['image'])) {
                        $slides[] = $attr;
                    }
                }
            }

            if (empty($slides)) {
                return '';
            }

            return Theme::partial('shortcodes.hero-slider', compact('slides'));
        });
    }

    // Shortcodes con template almacenado en la tabla `shortcodes` (render_template)
    add_db_shortcode('about-block');
    add_db_shortcode('proceso-steps');
    add_db_shortcode('provider-logos');
    add_db_shortcode('footer-ticker');
    add_db_shortcode('why-choose');
    add_db_shortcode('faq-page');

    // Shortcodes de contacto y secciones reutilizables
    add_db_shortcode('contact-info');
    add_db_shortcode('ctf-section');
    add_db_shortcode('coverage-section');
    add_db_shortcode('contact-faq');
    add_db_shortcode('cta-section');
    add_db_shortcode('services-grid');
    add_db_shortcode('services-types');
    add_db_shortcode('commitments');

    // Shortcodes de página Nosotros
    add_db_shortcode('company-history');
    add_db_shortcode('company-pillars');
    add_db_shortcode('company-team');
    add_db_shortcode('certifications');

    // Shortcodes de página Presupuesto
    add_db_shortcode('estimate-hero');

    // Shortcodes parametrizados por servicio
    add_db_shortcode_keyed('service-content', 'service-content');
    add_db_shortcode_keyed('service-faq', 'service-faq');
    add_db_shortcode_keyed('service-coverage', 'service-coverage');

});
