<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de media y contenido dinámico del template Riode.
 *
 * Shortcodes registrados:
 *   [blog-posts]     — Listado dinámico de posts (6 layouts: grid, list, sm, mask, framed, carousel)
 *   [category-card]  — Tarjeta de categoría (7 variantes visuales)
 *   [category-grid]  — Contenedor grid/carousel para [category-card]
 *   [creative-grid]  — Mosaico tipo Pinterest con [grid-item] hijos
 *   [grid-item]      — Celda individual dentro de [creative-grid]
 *   [testimonials]   — Bloque de testimonios (5 layouts, con hijos [testimonial])
 *   [testimonial]    — Testimonio individual (child de [testimonials])
 */
class RiodeMediaShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerBlogPosts();
        $this->registerCategoryCard();
        $this->registerCategoryGrid();
        $this->registerCreativeGrid();
        $this->registerGridItem();
        $this->registerTestimonial();
        $this->registerTestimonials();
    }

    // -------------------------------------------------------------------------
    // [blog-posts layout="grid" cols="3" limit="6" source="latest"]
    // -------------------------------------------------------------------------

    protected function registerBlogPosts(): void
    {
        $this->compiler->register('blog-posts', function (array $attrs, string $content): string {
            return view('riode::shortcodes.blog-posts', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-newspaper',
            'description' => 'Listado dinámico de posts del blog. 6 layouts: grid, list, sm, mask, framed, carousel.',
            'example' => '[blog-posts layout="grid" cols="3" limit="6" source="latest"]',
            'attributes' => [
                'layout' => 'Variante visual: grid | list | sm | mask | framed | carousel (default: grid)',
                'cols' => 'Columnas en desktop (1-4, default: 3)',
                'cols-tablet' => 'Columnas tablet (1-4, default: 2)',
                'cols-mobile' => 'Columnas mobile (1-2, default: 1)',
                'source' => 'Origen: latest | category | featured | manual (default: latest)',
                'category' => 'Slug de categoría (si source=category)',
                'ids' => 'IDs separados por coma (si source=manual)',
                'limit' => 'Cantidad máxima de posts (1-24, default: 6)',
                'order-by' => 'Campo de orden: published_at | views | title | created_at',
                'order-dir' => 'Dirección: asc | desc (default: desc)',
                'show-date' => 'Mostrar fecha: true | false (default: true)',
                'show-meta' => 'Mostrar meta (autor + comentarios): true | false (default: true)',
                'show-description' => 'Mostrar excerpt: true | false (default: true)',
                'show-button' => 'Mostrar botón Leer más: true | false (default: true)',
                'button-text' => 'Texto del botón (default: Leer más)',
                'carousel' => 'Modo carrusel: true | false (default: false)',
                'autoplay' => 'Autoplay del carrusel: true | false (default: false)',
                'autoplay-speed' => 'ms entre slides (default: 5000)',
                'class' => 'Clases CSS extra',
            ],
            'cacheable' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // [category-card image="..." name="..." href="..." count="42"]
    // -------------------------------------------------------------------------

    protected function registerCategoryCard(): void
    {
        $this->compiler->register('category-card', function (array $attrs, string $content): string {
            if (empty($attrs['name']) || empty($attrs['href'])) {
                return '';
            }

            return view('riode::shortcodes.category-card', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-tags',
            'description' => 'Tarjeta visual de categoría. 7 variantes: default, light, icon, ellipse, semi-circle, classic, badge.',
            'example' => '[category-card image="/img/cat.jpg" name="Hombre" href="/cat/hombre" count="42" style="default"]',
            'attributes' => [
                'name' => 'Nombre de la categoría (requerido)',
                'href' => 'URL destino (requerido)',
                'image' => 'Imagen de la categoría (URL)',
                'icon' => 'Icono Font Awesome 6 (alternativa a image)',
                'description' => 'Subtítulo / descripción corta',
                'count' => 'Número de productos',
                'count-label' => 'Texto tras el número (default: Productos)',
                'style' => 'Variante: default | light | icon | ellipse | semi-circle | classic | badge',
                'shape' => 'Forma: default | rounded | ellipse | absolute',
                'hover' => 'Efecto hover: none | zoom | light | dark (default: zoom)',
                'bg-color' => 'Color de fondo hex (solo style=icon)',
                'button-text' => 'Texto del botón',
                'button-href' => 'URL del botón (si difiere de href)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [category-grid cols="4" layout="grid"]...[/category-grid]
    // -------------------------------------------------------------------------

    protected function registerCategoryGrid(): void
    {
        $this->compiler->register('category-grid', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.category-grid', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-th-large',
            'description' => 'Contenedor grid o carrusel responsive para shortcodes [category-card].',
            'example' => '[category-grid cols="4" layout="grid"][category-card name="Hombre" href="/hombre"][/category-grid]',
            'attributes' => [
                'layout' => 'Estructura: grid | carousel (default: grid)',
                'cols' => 'Columnas en desktop (1-5, default: 4)',
                'cols-tablet' => 'Columnas tablet (1-4, default: 2)',
                'cols-mobile' => 'Columnas mobile (1-3, default: 1)',
                'gutter' => 'Separación: none | sm | md | lg (default: md)',
                'autoplay' => 'Autoplay del carrusel: true | false',
                'autoplay-speed' => 'ms entre slides (default: 5000)',
                'show-arrows' => 'Flechas del carrusel: true | false (default: true)',
                'show-dots' => 'Indicadores: true | false (default: false)',
                'align' => 'Alineación interna: left | center | right (default: center)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [creative-grid gutter="md"]...[/creative-grid]
    // -------------------------------------------------------------------------

    protected function registerCreativeGrid(): void
    {
        $this->compiler->register('creative-grid', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.creative-grid', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-shapes',
            'description' => 'Mosaico tipo Pinterest con [grid-item] hijos de tamaños mixtos.',
            'example' => '[creative-grid gutter="md"][grid-item cols="8" height="x2"][banner image="/big.jpg" title="Outlet"][/grid-item][/creative-grid]',
            'attributes' => [
                'gutter' => 'Espacio entre items: none | sm | md | lg (default: md)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [grid-item cols="8" height="x2" animation="fadeInRight"]...[/grid-item]
    // -------------------------------------------------------------------------

    protected function registerGridItem(): void
    {
        $this->compiler->register('grid-item', function (array $attrs, string $content): string {
            return view('riode::shortcodes.grid-item', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Celda individual dentro de [creative-grid]. Define ancho (cols) y alto (height).',
            'example' => '[grid-item cols="8" height="x2" animation="fadeInRight"][banner image="/big.jpg" title="Outlet"][/grid-item]',
            'attributes' => [
                'cols' => 'Ancho 1-12 en Bootstrap lg (requerido)',
                'cols-sm' => 'Ancho en sm breakpoint',
                'cols-md' => 'Ancho en md breakpoint',
                'height' => 'Proporción de alto: x1 | x15 | x2 | x25 | x3 (default: x1)',
                'animation' => 'Animación de entrada: fadeInLeft | fadeInRight | fadeInUp | fadeInDown | ... | none',
                'delay' => 'Delay de la animación CSS (default: 0s)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [testimonial author="Ana García" role="Cliente" rating="5"]...[/testimonial]
    // Child de [testimonials]
    // -------------------------------------------------------------------------

    protected function registerTestimonial(): void
    {
        $this->compiler->register('testimonial', function (array $attrs, string $content): string {
            if (empty($attrs['author'])) {
                return '';
            }

            return view('riode::shortcodes.testimonial', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Testimonio individual dentro de [testimonials]. El contenido entre tags es la cita.',
            'example' => '[testimonial author="Ana García" role="Clienta" rating="5" avatar="/img/ana.jpg"]Excelente servicio[/testimonial]',
            'attributes' => [
                'author' => 'Nombre del autor (requerido)',
                'role' => 'Cargo / descripción',
                'avatar' => 'URL de la foto del autor',
                'rating' => 'Valoración 0-5 (entero o decimal)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [testimonials layout="centered-border" cols="3"]...[/testimonials]
    // -------------------------------------------------------------------------

    protected function registerTestimonials(): void
    {
        $this->compiler->register('testimonials', function (array $attrs, string $content): string {
            return view('riode::shortcodes.testimonials', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Bloque de testimonios con 5 layouts. Acepta hijos [testimonial] o fuente dinámica.',
            'example' => '[testimonials layout="centered-border" cols="3"][testimonial author="Ana" rating="5"]Gran servicio[/testimonial][/testimonials]',
            'attributes' => [
                'layout' => 'Estilo: default | inversed | centered | centered-border | centered-bg (default: default)',
                'cols' => 'Columnas (1-4, default: 3)',
                'carousel' => 'Modo carrusel Owl: true | false (default: true)',
                'autoplay' => 'Autoplay: true | false (default: false)',
                'nav' => 'Posición flechas: top | bottom | none (default: top)',
                'dots' => 'Puntos de navegación: true | false (default: false)',
                'background' => 'Fondo de sección: light | grey | dark | parallax (default: light)',
                'background-image' => 'URL imagen (solo si background=parallax)',
                'title' => 'Título sobre el bloque',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }
}
