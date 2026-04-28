<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de utilidad y media del template Riode.
 *
 * Shortcodes registrados:
 *   [breadcrumb]    — Migas de pan con 3 separadores y 3 variantes de fondo
 *   [page-header]   — Hero interno con título, subtítulo, fondo y breadcrumb integrado
 *   [social-links]  — Iconos de redes sociales con FA6 (13 redes soportadas)
 *   [image-box]     — Tarjeta imagen + nombre + cargo + redes (3 layouts)
 *   [video]         — Reproductor en 3 modos: popup, inline, background
 */
class RiodeUtilityShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerBreadcrumb();
        $this->registerPageHeader();
        $this->registerSocialLinks();
        $this->registerImageBox();
        $this->registerVideo();
    }

    // -------------------------------------------------------------------------
    // [breadcrumb items='[{"label":"Inicio","url":"/"},{"label":"Página"}]']
    // -------------------------------------------------------------------------

    protected function registerBreadcrumb(): void
    {
        $this->compiler->register('breadcrumb', function (array $attrs, string $content): string {
            $items = $attrs['items'] ?? null;

            if (! $items) {
                return '';
            }

            $parsed = is_string($items) ? json_decode($items, true) : (array) $items;

            if (! is_array($parsed) || empty($parsed)) {
                return '';
            }

            return view('riode::shortcodes.breadcrumb', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-route',
            'description' => 'Migas de pan de navegación. Admite 3 separadores y 3 variantes de fondo.',
            'example' => '[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Productos","url":"/productos"},{"label":"Detalle"}]\' separator="chevron" background="grey"]',
            'attributes' => [
                'items' => 'JSON array de items: [{"label":"...","url":"..."},...]. El último item no lleva url (requerido)',
                'separator' => 'Separador entre items: slash | chevron | dot (default: slash)',
                'background' => 'Fondo del wrapper: grey | dark | none (default: grey)',
                'show-home-icon' => 'Reemplaza primer label por icono casa FA6: true | false (default: true)',
                'align' => 'Alineación: left | center | right (default: left)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [page-header title="Productos" layout="centered" background="dark"]
    // -------------------------------------------------------------------------

    protected function registerPageHeader(): void
    {
        $this->compiler->register('page-header', function (array $attrs, string $content): string {
            if (empty($attrs['title'])) {
                return '';
            }

            return view('riode::shortcodes.page-header', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-window-maximize',
            'description' => 'Encabezado de sección con título grande, subtítulo y migas de pan. Ideal para heros internos.',
            'example' => '[page-header title="Sobre nosotros" subtitle="Conoce nuestra historia" layout="centered" background="dark" breadcrumb=\'[{"label":"Inicio","url":"/"},{"label":"Sobre nosotros"}]\']',
            'attributes' => [
                'title' => 'Título principal (requerido)',
                'subtitle' => 'Subtítulo bajo el título',
                'layout' => 'Disposición: centered | inline | start (default: centered)',
                'background' => 'Tipo de fondo: dark | grey | image | none (default: dark)',
                'background-image' => 'URL imagen de fondo (solo cuando background=image)',
                'breadcrumb' => 'JSON array de migas: [{"label":"Inicio","url":"/"},{"label":"Actual"}]',
                'text-color' => 'Color del texto: light | dark (default: light)',
                'min-height' => 'Alto mínimo: sm | md | lg (default: md)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [social-links networks='{"facebook":"https://...","instagram":"https://..."}']
    // -------------------------------------------------------------------------

    protected function registerSocialLinks(): void
    {
        $this->compiler->register('social-links', function (array $attrs, string $content): string {
            $networks = $attrs['networks'] ?? null;

            if (! $networks) {
                return '';
            }

            $parsed = is_string($networks) ? json_decode($networks, true) : (array) $networks;

            if (! is_array($parsed) || empty($parsed)) {
                return '';
            }

            return view('riode::shortcodes.social-links', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-share-nodes',
            'description' => 'Iconos enlazados a redes sociales (13 redes con Font Awesome 6). 5 estilos visuales.',
            'example' => '[social-links style="rounded" size="md" align="center" networks=\'{"facebook":"https://facebook.com/marca","instagram":"https://instagram.com/marca","linkedin":"https://linkedin.com/company/marca"}\']',
            'attributes' => [
                'networks' => 'JSON objeto red→URL: {"facebook":"https://...","instagram":"https://..."} (requerido). Redes: facebook, twitter, x, instagram, linkedin, youtube, pinterest, tiktok, whatsapp, telegram, github, discord, email',
                'style' => 'Estilo visual: default | active | inline | square | rounded (default: default)',
                'size' => 'Tamaño iconos: sm | md | lg (default: md)',
                'align' => 'Alineación: left | center | right (default: left)',
                'color' => 'Color: default | brand | grey | white (default: brand)',
                'target' => 'Apertura enlace: _blank | _self (default: _blank)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [image-box image="/team/ana.jpg" name="Ana García" job="CEO"]
    // -------------------------------------------------------------------------

    protected function registerImageBox(): void
    {
        $this->compiler->register('image-box', function (array $attrs, string $content): string {
            if (empty($attrs['image'])) {
                return '';
            }

            return view('riode::shortcodes.image-box', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-id-badge',
            'description' => 'Tarjeta con imagen + nombre + cargo + redes sociales. 3 layouts: default, classic y overlay.',
            'example' => '[image-box image="/team/ana.jpg" name="Ana García" job="CEO" social-facebook="https://fb.com/ana" social-linkedin="https://linkedin.com/in/ana"]',
            'attributes' => [
                'image' => 'URL imagen principal (requerido)',
                'alt' => 'Alt de la imagen',
                'name' => 'Nombre o título principal',
                'job' => 'Cargo, ciudad o categoría',
                'layout' => 'Disposición: default | classic | overlay (default: default)',
                'radius' => 'Borde redondeado en imagen: true | false (default: true)',
                'email' => 'Email (visible en layout=overlay)',
                'phone' => 'Teléfono (visible en layout=overlay)',
                'social-facebook' => 'URL perfil Facebook',
                'social-twitter' => 'URL perfil Twitter/X',
                'social-linkedin' => 'URL perfil LinkedIn',
                'social-instagram' => 'URL perfil Instagram',
                'social-youtube' => 'URL canal YouTube',
                'animation' => 'Clase de animación Animate.css (ej: fadeInLeft)',
                'animation-delay' => 'Delay de la animación CSS (ej: .2s)',
                'width' => 'Ancho de la imagen en px (default: 280)',
                'height' => 'Alto de la imagen en px (default: 280)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [video src="/video/intro.mp4" mode="popup" cover="/img/cover.jpg"]
    // -------------------------------------------------------------------------

    protected function registerVideo(): void
    {
        $this->compiler->register('video', function (array $attrs, string $content): string {
            if (empty($attrs['src'])) {
                return '';
            }

            // Autoplay implica muted (los navegadores bloquean autoplay sin muted).
            $autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($autoplay && ! isset($attrs['muted'])) {
                $attrs['muted'] = 'true';
            }

            // YouTube/Vimeo en mode=inner no se puede hacer inline swap; degradar a popup.
            $src = $attrs['src'];
            $isExternal = str_contains($src, 'youtube.com')
                || str_contains($src, 'youtu.be')
                || str_contains($src, 'vimeo.com');

            if ($isExternal && ($attrs['mode'] ?? '') === 'inner') {
                $attrs['mode'] = 'popup';
            }

            return view('riode::shortcodes.video', compact('attrs', 'content'))->render();
        }, [
            'icon' => 'fa-video',
            'description' => 'Reproductor de vídeo (YouTube, Vimeo, MP4) en 3 modos: popup (lightbox), inline (iframe/video), background (autoplay loop muted).',
            'example' => '[video src="/video/intro.mp4" mode="popup" cover="/img/cover.jpg" title="Conoce nuestro proceso"]',
            'attributes' => [
                'src' => 'URL del vídeo: MP4 local, YouTube o Vimeo (requerido)',
                'mode' => 'Modo de reproducción: popup | inline | background (default: inline)',
                'cover' => 'Imagen previa / poster (URL)',
                'cover-alt' => 'Alt de la imagen cover',
                'title' => 'Título overlay (mode=popup)',
                'subtitle' => 'Subtítulo overlay (mode=popup)',
                'autoplay' => 'Auto reproducir (mode=inline/background): true | false (default: false)',
                'loop' => 'Bucle continuo: true | false (default: false)',
                'muted' => 'Silenciado (obligatorio si autoplay=true): true | false (default: false)',
                'controls' => 'Controles HTML5: true | false (default: true)',
                'text-color' => 'Color del texto overlay: light | dark (default: light)',
                'width' => 'Ancho del cover/vídeo (default: 1843)',
                'height' => 'Alto del cover/vídeo (default: 606)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }
}
