<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de efectos decorativos y animación del template Riode.
 *
 * Shortcodes registrados:
 *   [animate]       — Wrapper animate.css con IntersectionObserver (46+ animaciones)
 *   [floating]      — Mouse parallax (vanilla JS, desactivado en touch devices)
 *   [scroll-reveal] — Parallax de scroll (IntersectionObserver + transform CSS)
 *   [svg-float]     — SVG decorativo con morphing animado por JS
 */
class RiodeEffectsShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerAnimate();
        $this->registerFloating();
        $this->registerScrollReveal();
        $this->registerSvgFloat();
    }

    // -------------------------------------------------------------------------
    // [animate name="fadeInUpShorter" duration="1s" delay=".3s"]content[/animate]
    // -------------------------------------------------------------------------

    protected function registerAnimate(): void
    {
        $this->compiler->register('animate', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.animate', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Wrapper de animación de entrada en viewport. Activa la animación cuando el elemento entra en el viewport usando IntersectionObserver.',
            'example' => '[animate name="fadeInUpShorter" duration="1s" delay=".3s"][title size="lg"]Bienvenido[/title][/animate]',
            'attributes' => [
                'name' => 'Nombre de la animación animate.css (default: fadeIn). 46 variantes disponibles.',
                'duration' => 'Duración CSS (default: 1s)',
                'delay' => 'Retardo CSS antes de iniciar (default: 0s)',
                'slide-mode' => 'Si true, usa slide-animate para uso dentro de [slider] (default: false)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [floating depth="2" invert-x="true" invert-y="true"]content[/floating]
    // -------------------------------------------------------------------------

    protected function registerFloating(): void
    {
        $this->compiler->register('floating', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.floating', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Mouse parallax decorativo. El contenido sigue el cursor suavemente. Se desactiva automáticamente en touch devices y prefers-reduced-motion.',
            'example' => '[floating depth="2"][image src="/img/decor-leaf.png" alt="hoja"][/floating]',
            'attributes' => [
                'depth' => 'Intensidad del paralaje 0.1–5 (default: 1). Recomendado: 0.5 lento, 3 rápido.',
                'invert-x' => 'Inversión horizontal: true | false (default: true)',
                'invert-y' => 'Inversión vertical: true | false (default: true)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [scroll-reveal speed="slow" direction="up"]content[/scroll-reveal]
    // -------------------------------------------------------------------------

    protected function registerScrollReveal(): void
    {
        $this->compiler->register('scroll-reveal', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            return view('riode::shortcodes.scroll-reveal', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Parallax de scroll: anima la posición del contenido en función del scroll. Usa IntersectionObserver + CSS transforms (no skrollr).',
            'example' => '[scroll-reveal speed="slow" direction="up"][title size="lg"]Aparece despacio[/title][/scroll-reveal]',
            'attributes' => [
                'speed' => 'Magnitud del desplazamiento: slow | normal | fast | entire (default: normal)',
                'direction' => 'Eje y sentido: up | down | left | right (default: up)',
                'from' => 'Override del transform inicial CSS (ej: "transform: translate(0, 50%);")',
                'to' => 'Override del transform final CSS (ej: "transform: translate(0, -50%);")',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [svg-float svg-path="M0,70V0h70v70H0z..." delta="2.8" image="/img/prod.png"]
    // -------------------------------------------------------------------------

    protected function registerSvgFloat(): void
    {
        $this->compiler->register('svg-float', function (array $attrs, string $content): string {
            if (empty($attrs['svg-path'])) {
                return '';
            }

            return view('riode::shortcodes.svg-float', compact('attrs', 'content'))->render();
        }, [
            'description' => 'SVG decorativo con morphing animado de path. Soporta imagen de fondo, imagen sobrepuesta y badge/logo. Self-closing.',
            'example' => '[svg-float svg-path="M0,70V0h70v70H0z M50.7,30.4c..." delta="2.8" speed="2" image="/img/product.png" fill="#90bb13"]',
            'attributes' => [
                'svg-path' => 'Atributo "d" del path SVG (requerido)',
                'delta' => 'Intensidad del morphing 0–3 (default: 1)',
                'speed' => 'Velocidad de animación 0.5–5 (default: 2)',
                'size' => 'Amplitud del muestreo de puntos 1–50 (default: 14)',
                'viewbox' => 'viewBox del SVG (default: "0 -1 70 72")',
                'width' => 'Ancho declarado del SVG en px (default: 980)',
                'height' => 'Alto declarado del SVG en px (default: 980)',
                'fill' => 'Color de relleno del path (default: currentColor)',
                'image' => 'URL de imagen sobrepuesta dentro del wrapper',
                'image-alt' => 'Alt de la imagen sobrepuesta',
                'background' => 'URL de imagen de fondo detrás del SVG',
                'background-alt' => 'Alt de la imagen de fondo',
                'notice-image' => 'URL de badge/logo sobrepuesto (figure.svg-notice)',
            ],
        ]);
    }
}
