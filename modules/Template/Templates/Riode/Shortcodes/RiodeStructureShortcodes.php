<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de estructura y media del template Riode.
 *
 * Implementa: [title], [tabs]+[tab], [slider]+[slide], [banner],
 * [hotspot]+[hotspot-pin].
 *
 * Seguridad: los atributos de tipo URL, class e ID se pasan a la vista
 * ya escapados (htmlspecialchars o e()). El $content de shortcodes padre
 * se inserta con {!! !!} porque ya viene compilado por el ShortcodeCompiler
 * y puede contener HTML legítimo de los hijos.
 */
class RiodeStructureShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerTitle();
        $this->registerTabs();
        $this->registerTab();
        $this->registerSlider();
        $this->registerSlide();
        $this->registerBanner();
        $this->registerHotspot();
        $this->registerHotspotPin();
    }

    // -------------------------------------------------------------------------
    // [title size="md" align="center" decoration="none"]Texto[/title]
    // -------------------------------------------------------------------------

    protected function registerTitle(): void
    {
        $this->compiler->register('title', function (array $attrs, string $content): string {
            return view('riode::shortcodes.title', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-heading',
            'description' => 'Encabezado decorado para secciones. Soporta 9 variantes visuales del template Riode.',
            'example' => '[title size="lg" align="center"]Por qué elegirnos[/title]',
            'attributes' => [
                'level' => 'Etiqueta semántica: h1|h2|h3|h4|h5|h6 (default: h2)',
                'size' => 'Tamaño visual: sm|md|lg (default: md)',
                'align' => 'Alineación: left|center|right (default: center)',
                'decoration' => 'Decoración: none|cross-left|cross-center|cross-right|underline-simple|underline-active|underline-custom (default: none)',
                'description' => 'Párrafo descriptivo bajo el título',
                'color' => 'Color: default|primary|white|dark (default: default)',
                'uppercase' => 'Mayúsculas: true|false (default: false)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [tabs style="simple" align="left"]...[/tabs]
    // -------------------------------------------------------------------------

    protected function registerTabs(): void
    {
        $this->compiler->register('tabs', function (array $attrs, string $content): string {
            // El content ya contiene los [tab] hijos compilados como HTML crudo.
            // Los hijos emiten dos partes separadas por un marcador para que
            // [tabs] pueda dividir nav-items de tab-panes.
            return view('riode::shortcodes.tabs', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-folder-tree',
            'description' => 'Sistema de pestañas. Acepta hijos [tab]. Soporta 9 estilos Riode.',
            'example' => '[tabs style="solid" align="center"][tab title="Descripción" active="true"]Texto[/tab][tab title="Envío"]24-48h[/tab][/tabs]',
            'attributes' => [
                'style' => 'Estilo: simple|solid|outline|outline2|inverse|boxed-simple (default: simple)',
                'orientation' => 'Orientación: horizontal|vertical (default: horizontal)',
                'align' => 'Alineación nav: left|center|right (default: left)',
                'shape' => 'Forma botones: square|round (default: square)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [tab title="..." active="true" icon="fas fa-..."]contenido[/tab]
    // -------------------------------------------------------------------------

    protected function registerTab(): void
    {
        // El tab hijo emite un marcador especial separando nav-item y tab-pane,
        // para que el padre [tabs] pueda reconstruir la estructura correcta.
        $this->compiler->register('tab', function (array $attrs, string $content): string {
            return view('riode::shortcodes.tab', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-folder',
            'description' => 'Pestaña individual dentro de [tabs]. Requiere atributo title.',
            'example' => '[tab title="Envío" icon="fas fa-truck" active="true"]Contenido[/tab]',
            'attributes' => [
                'title' => 'Texto de la pestaña (requerido)',
                'active' => 'Activa por defecto: true|false (default: false)',
                'icon' => 'Icono Font Awesome 6 (ej: fas fa-truck)',
                'id' => 'ID personalizado para deep-linking',
                'class' => 'Clases CSS extra del tab-pane',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [slider effect="fade" autoplay="true" nav="true"]...[/slider]
    // -------------------------------------------------------------------------

    protected function registerSlider(): void
    {
        $this->compiler->register('slider', function (array $attrs, string $content): string {
            return view('riode::shortcodes.slider', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-images',
            'description' => 'Carrusel hero con Owl Carousel 2. Acepta hijos [slide].',
            'example' => '[slider effect="fade" autoplay="true" nav="true"][slide image="/img/hero.jpg" title="Bienvenidos" button-text="Ver más" button-href="/productos"][/slider]',
            'attributes' => [
                'effect' => 'Efecto: fade|zoom|rotate|slide|none (default: fade)',
                'autoplay' => 'Avance automático: true|false (default: false)',
                'autoplay-timeout' => 'Intervalo en ms (default: 5000)',
                'loop' => 'Bucle: true|false (default: false)',
                'nav' => 'Flechas: true|false (default: true)',
                'dots' => 'Puntos: true|false (default: false)',
                'nav-style' => 'Estilo flechas: bg|arrow|bg-arrow|simple (default: bg)',
                'dots-style' => 'Estilo dots: default|inner|white (default: default)',
                'height' => 'Altura: auto|sm|md|lg|full (default: md)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [slide image="..." title="..." button-text="..." button-href="..."][/slide]
    // -------------------------------------------------------------------------

    protected function registerSlide(): void
    {
        $this->compiler->register('slide', function (array $attrs, string $content): string {
            return view('riode::shortcodes.slide', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-rectangle-ad',
            'description' => 'Slide individual dentro de [slider]. Imagen de fondo + título + CTA.',
            'example' => '[slide image="/img/hero.jpg" subtitle="Online Store" title="Nueva colección" button-text="Comprar" button-href="/tienda" text-align="left"]',
            'attributes' => [
                'image' => 'Imagen de fondo (URL)',
                'image-alt' => 'Alt de la imagen',
                'bg-color' => 'Color de fondo alternativo (hex)',
                'title' => 'Título del slide',
                'subtitle' => 'Subtítulo',
                'description' => 'Descripción',
                'button-text' => 'Texto del CTA',
                'button-href' => 'URL del CTA',
                'button-style' => 'Estilo botón: primary|dark|outline-white|outline-dark (default: dark)',
                'text-align' => 'Alineación: left|center|right (default: left)',
                'text-color' => 'Color texto: dark|light (default: dark)',
                'vertical-position' => 'Posición vertical: top|middle|bottom (default: middle)',
                'animations' => 'JSON de animaciones por elemento: {"title":"blurIn","subtitle":"fadeInUpShorter"}',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [banner image="..." type="image" title="..." hover-effect="zoom"]
    // -------------------------------------------------------------------------

    protected function registerBanner(): void
    {
        $this->compiler->register('banner', function (array $attrs, string $content): string {
            return view('riode::shortcodes.banner', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-rectangle-ad',
            'description' => 'Banner promocional con imagen de fondo. Soporta parallax, video popup, hover effects, filtros CSS y animación kenburns.',
            'example' => '[banner image="/img/sale.jpg" title="Outlet" subtitle="Hasta 70% Off" button-text="Ver ofertas" button-href="/outlet" text-color="light" animation="blurIn"]',
            'attributes' => [
                'image' => 'Imagen de fondo (URL)',
                'type' => 'Tipo: image|video|parallax (default: image)',
                'image-alt' => 'Alt de la imagen',
                'bg-color' => 'Color de fondo (hex)',
                'height' => 'Altura: auto|sm|md|lg|full (default: auto)',
                'parallax-offset' => 'Offset parallax en px (default: -60)',
                'video-src' => 'URL del video (type=video)',
                'title' => 'Título',
                'subtitle' => 'Subtítulo',
                'description' => 'Descripción',
                'text-align' => 'Alineación: left|center|right (default: left)',
                'text-color' => 'Color texto: light|dark (default: light)',
                'vertical-position' => 'Posición vertical: top|middle|bottom (default: middle)',
                'horizontal-position' => 'Posición horizontal: left|center|right (default: left)',
                'button-text' => 'Texto del CTA',
                'button-href' => 'URL del CTA',
                'button-style' => 'Estilo: primary|dark|white|outline-white|outline-dark (default: primary)',
                'button-shape' => 'Forma: rounded|ellipse|square (default: rounded)',
                'animation' => 'Animación de entrada del contenido (ej: blurIn, fadeInUp)',
                'hover-effect' => 'Hover: none|effect1|effect2|effect3|effect4|zoom|light|dark|zoom-light|zoom-dark (default: none)',
                'filter' => 'Filtro hover: none|blur|brightness|contrast|grayscale|hue|opacity|saturate|sepia (default: none)',
                'kenburns' => 'Kenburns: none|left|right|top|bottom (default: none)',
                'kenburns-duration' => 'Duración kenburns CSS (default: 20s)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [hotspot image="..."]...[/hotspot]
    // -------------------------------------------------------------------------

    protected function registerHotspot(): void
    {
        $this->compiler->register('hotspot', function (array $attrs, string $content): string {
            return view('riode::shortcodes.hotspot', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-crosshairs',
            'description' => 'Imagen interactiva con pins (hotspots). Acepta hijos [hotspot-pin]. Útil para look books y fichas de producto sobre imagen.',
            'example' => '[hotspot image="/img/sala.jpg" alt="Sala completa" animation="fadeIn"][hotspot-pin top="30%" left="50%" title="Sillón" price="$299" button-href="/product/sillon"][/hotspot]',
            'attributes' => [
                'image' => 'Imagen base (URL, requerido)',
                'alt' => 'Alt de la imagen',
                'width' => 'Ancho de imagen (default: 1180)',
                'height' => 'Alto de imagen (default: 600)',
                'animation' => 'Animación de entrada (ej: fadeIn)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [hotspot-pin top="11%" left="41%" title="Producto" price="$21"]
    // -------------------------------------------------------------------------

    protected function registerHotspotPin(): void
    {
        $this->compiler->register('hotspot-pin', function (array $attrs, string $content): string {
            return view('riode::shortcodes.hotspot-pin', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-thumbtack',
            'description' => 'Pin interactivo dentro de [hotspot]. Posición dinámica con top/left en %. Muestra tooltip con imagen, precio y CTA al hover.',
            'example' => '[hotspot-pin top="30%" left="50%" title="Bolso CK" image="/img/bolso.jpg" price="$21.00" button-href="/product/bolso" tooltip-position="bottom"]',
            'attributes' => [
                'top' => 'Posición vertical en % (requerido, ej: "30%")',
                'left' => 'Posición horizontal en % (requerido, ej: "50%")',
                'title' => 'Nombre del producto (requerido)',
                'style' => 'Estilo pin: plus|circle-filled|circle-outline|plus-filled (default: plus)',
                'animation' => 'Animación pin: spread|flash|none (default: spread)',
                'tooltip-position' => 'Posición tooltip: top|bottom|left|right (default: top)',
                'title-href' => 'URL del título',
                'image' => 'Imagen del tooltip',
                'price' => 'Precio (ej: "$21.00")',
                'price-old' => 'Precio tachado',
                'button-text' => 'Texto del CTA (default: "Ver producto")',
                'button-href' => 'URL del CTA',
                'button-style' => 'Estilo botón: primary|dark|outline (default: primary)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }
}
