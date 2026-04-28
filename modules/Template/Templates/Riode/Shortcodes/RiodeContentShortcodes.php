<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de contenido visual inspirados en la plantilla Riode.
 *
 * Shortcodes registrados:
 *   [cta]           — Bloque call-to-action (6 layouts: promo, subscribe, one-col, 2-cols, 3-cols, parallax)
 *   [cta-column]    — Columna hijo para layout 3-cols de [cta]
 *   [countdown]     — Cuenta regresiva vanilla JS (sin jquery.countdown)
 *   [counter]       — Cifra animada con IntersectionObserver
 *   [counter-grid]  — Contenedor responsive para [counter]
 *   [icon-box]      — Icono + título + descripción (8 variantes visuales)
 *   [icon-box-grid] — Contenedor grid/carousel para [icon-box]
 */
class RiodeContentShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerCta();
        $this->registerCountdown();
        $this->registerCounter();
        $this->registerCounterGrid();
        $this->registerIconBox();
        $this->registerIconBoxGrid();
    }

    // -------------------------------------------------------------------------
    // [cta layout="promo" title="..." button-text="..." button-href="/"]
    // -------------------------------------------------------------------------

    protected function registerCta(): void
    {
        // Child shortcode for 3-cols layout
        $this->compiler->register('cta-column', function (array $attrs, string $content): string {
            return view('riode::shortcodes.cta-column', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-grip-vertical',
            'description' => 'Columna para el layout 3-cols de [cta]. Admite image, title, subtitle, button-text, button-href.',
            'example' => '[cta-column image="/img/c1.jpg" title="Hombre" subtitle="Desde 29€" button-text="Ver"]',
            'attributes' => [
                'image' => 'URL de la imagen de la columna',
                'title' => 'Título de la columna',
                'subtitle' => 'Subtítulo de la columna',
                'button-text' => 'Texto del botón',
                'button-href' => 'URL del botón',
                'class' => 'Clases CSS extra',
            ],
        ]);

        $this->compiler->register('cta', function (array $attrs, string $content): string {
            return view('riode::shortcodes.cta', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-bullhorn',
            'description' => 'Bloque call-to-action con 6 layouts: promo, subscribe, one-col, 2-cols, 3-cols, parallax.',
            'example' => '[cta layout="promo" title="Trade-in" subtitle="Upgrade y ahorra" button-text="Comprar" button-href="/shop"]',
            'attributes' => [
                'layout' => 'Variante visual: promo | subscribe | one-col | 2-cols | 3-cols | parallax',
                'title' => 'Título principal',
                'subtitle' => 'Subtítulo (debajo del título)',
                'description' => 'Párrafo descriptivo',
                'background' => 'Fondo: white | grey | dark | image | parallax',
                'background-image' => 'URL imagen de fondo (si background=image|parallax)',
                'button-text' => 'Texto del botón principal',
                'button-href' => 'URL del botón',
                'button-style' => 'Estilo del botón: primary | dark | white-outline',
                'form-action' => 'URL del form (layouts subscribe y parallax)',
                'form-placeholder' => 'Placeholder del input de email',
                'form-button' => 'Texto del botón submit del form',
                'show-social' => 'Mostrar social links (solo parallax): true | false',
                'align' => 'Alineación: left | center | right',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [countdown until="2026-12-31 23:59:59" layout="block"]
    // -------------------------------------------------------------------------

    protected function registerCountdown(): void
    {
        $this->compiler->register('countdown', function (array $attrs, string $content): string {
            $until = $attrs['until'] ?? null;

            if (! $until) {
                return '';
            }

            $ts = is_numeric($until) ? (int) $until : strtotime($until);

            if ($ts === false || $ts <= time()) {
                return '';
            }

            return view('riode::shortcodes.countdown', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-clock',
            'description' => 'Cuenta regresiva hasta una fecha. Si la fecha ya pasó, no renderiza nada.',
            'example' => '[countdown until="2026-12-31 23:59:59" layout="block" /]',
            'attributes' => [
                'until' => 'Fecha objetivo ISO8601 (ej: 2026-12-31 23:59:59) o timestamp Unix (requerido)',
                'layout' => 'Distribución: inline-banner | block | 2-cols | 2-cols-split',
                'format' => 'Unidades: DHMS | HMS | MS | D | HM',
                'border' => 'Bordes entre cifras: true | false',
                'background' => 'Fondo: light | dark | primary | none',
                'text-color' => 'Color texto: light | dark',
                'title' => 'Título del banner (solo inline-banner)',
                'subtitle' => 'Subtítulo del banner (solo inline-banner)',
                'deal-image' => 'Imagen pequeña izquierda (solo inline-banner)',
                'button-text' => 'Texto del botón derecho (solo inline-banner)',
                'button-href' => 'URL del botón',
                'labels' => 'JSON con etiquetas: {"days":"Días","hours":"Horas","minutes":"Min","seconds":"Seg"}',
                'class' => 'Clases CSS extra',
            ],
            'cacheable' => false, // El tiempo restante cambia con cada request
        ]);
    }

    // -------------------------------------------------------------------------
    // [counter to="9500" title="Clientes felices" suffix="+"]
    // -------------------------------------------------------------------------

    protected function registerCounter(): void
    {
        $this->compiler->register('counter', function (array $attrs, string $content): string {
            if (! isset($attrs['to']) || $attrs['to'] === '') {
                return '';
            }

            return view('riode::shortcodes.counter', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-hashtag',
            'description' => 'Cifra animada (contador) que se incrementa al entrar en el viewport.',
            'example' => '[counter to="9500" title="Clientes felices" suffix="+" /]',
            'attributes' => [
                'to' => 'Valor final al que anima (requerido)',
                'title' => 'Texto descriptivo bajo el número (requerido)',
                'from' => 'Valor inicial (default: 0)',
                'duration' => 'Duración en ms (default: 2000)',
                'decimals' => 'Decimales (0–3, default: 0)',
                'delimiter' => 'Separador de miles (default: ",")',
                'prefix' => 'Símbolo antes del número (ej: $)',
                'suffix' => 'Símbolo después del número (ej: %, +)',
                'description' => 'Texto secundario debajo del title',
                'icon-image' => 'URL imagen-icono encima del número',
                'icon' => 'Clase Font Awesome 6 (ej: fas fa-users)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [counter-grid layout="boxed" cols="4"][counter ...][/counter-grid]
    // -------------------------------------------------------------------------

    protected function registerCounterGrid(): void
    {
        $this->compiler->register('counter-grid', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.counter-grid', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-table-cells',
            'description' => 'Contenedor grid responsive para shortcodes [counter]. Define columnas y estilo visual.',
            'example' => '[counter-grid layout="boxed" cols="4"][counter to="9500" title="Clientes" /][/counter-grid]',
            'attributes' => [
                'layout' => 'Estilo visual: boxed | block | simple | background | inline',
                'cols' => 'Columnas en desktop (1–4, default: 4)',
                'cols-tablet' => 'Columnas en tablet (1–4, default: 2)',
                'cols-mobile' => 'Columnas en mobile (1–2, default: 1)',
                'gutter' => 'Separación: none | sm | md | lg',
                'background' => 'Fondo: light | dark | image | none',
                'background-image' => 'URL imagen de fondo (si background=image)',
                'align' => 'Alineación texto: left | center | right',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [icon-box icon="fas fa-truck" title="Envío gratis" description="..."]
    // -------------------------------------------------------------------------

    protected function registerIconBox(): void
    {
        $this->compiler->register('icon-box', function (array $attrs, string $content): string {
            if (empty($attrs['icon']) || empty($attrs['title'])) {
                return '';
            }

            return view('riode::shortcodes.icon-box', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-cube',
            'description' => 'Bloque visual icono + título + descripción. 8 variantes: default, border, inversed, solid, tiny (top/side).',
            'example' => '[icon-box icon="fas fa-truck" title="Envío gratis" description="Pedidos +50€" style="border" /]',
            'attributes' => [
                'icon' => 'Clase Font Awesome 6 (ej: fas fa-truck) — requerido',
                'title' => 'Título del bloque — requerido',
                'description' => 'Texto descriptivo bajo el título',
                'layout' => 'Posición del icono: top | side',
                'style' => 'Estilo visual: default | border | inversed | solid | tiny',
                'color' => 'Color del icono: default | primary | secondary | dark',
                'href' => 'URL — hace la caja entera clickable',
                'align' => 'Alineación: left | center | right',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [icon-box-grid cols="4"][icon-box ...][/icon-box-grid]
    // -------------------------------------------------------------------------

    protected function registerIconBoxGrid(): void
    {
        $this->compiler->register('icon-box-grid', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.icon-box-grid', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-border-all',
            'description' => 'Contenedor grid o carrusel responsive para shortcodes [icon-box].',
            'example' => '[icon-box-grid cols="4" gutter="md"][icon-box icon="fas fa-truck" title="Envío" /][/icon-box-grid]',
            'attributes' => [
                'layout' => 'Estructura: grid | carousel',
                'cols' => 'Columnas en desktop (1–4, default: 3)',
                'cols-tablet' => 'Columnas en tablet (1–4, default: 2)',
                'cols-mobile' => 'Columnas en mobile (1–2, default: 1)',
                'gutter' => 'Separación: none | sm | md | lg',
                'background' => 'Fondo: light | grey | dark | none',
                'autoplay' => 'Autoplay carrusel: true | false',
                'autoplay-speed' => 'ms entre slides (default: 5000)',
                'show-arrows' => 'Flechas del carrusel: true | false',
                'show-dots' => 'Indicadores del carrusel: true | false',
                'align' => 'Alineación texto: left | center | right',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }
}
