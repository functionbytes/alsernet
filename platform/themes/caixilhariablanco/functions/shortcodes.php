<?php

use Modules\Template\Facades\Theme;

/**
 * Shortcodes del tema Wowy
 *
 * Los shortcodes del tema que requieren módulos de ecommerce/blog/faq
 * se registran en la base de datos desde:
 * Admin → Configuración → Shortcodes
 *
 * Los shortcodes genéricos compatibles con inoqualabs se definen aquí.
 */
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

});
