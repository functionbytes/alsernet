<?php

namespace Modules\Shortcode\Shortcodes;

use Illuminate\Support\Facades\Log;
use Modules\Media\Models\Media;
use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de contenido/presentación por defecto del módulo.
 *
 * Maquetan HTML con Bootstrap 5.3 + variantes Riode: button, alert, columns,
 * column, youtube, image, icon, badge, card, accordion, accordion-item, quote,
 * contact-form.
 *
 * Cada uno se registra sólo si config('shortcode.default_shortcodes.{name}')
 * no es false, permitiendo desactivar individualmente desde la config.
 */
class ContentShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerButton();
        $this->registerAlert();
        $this->registerColumns();
        $this->registerColumn();
        $this->registerYoutube();
        $this->registerImage();
        $this->registerIcon();
        $this->registerBadge();
        $this->registerCard();
        $this->registerAccordion();
        $this->registerAccordionItem();
        $this->registerQuote();
        $this->registerContactForm();
    }

    /**
     * Registra el shortcode sólo si no está desactivado en config.
     *
     * @param  array<string, mixed>  $meta
     */
    private function register(string $name, callable $callback, array $meta = []): void
    {
        $enabled = config('shortcode.default_shortcodes', []);

        if (($enabled[$name] ?? true) === false) {
            return;
        }

        $this->compiler->register($name, $callback, $meta);
    }

    // ---------------------------------------------------------------------
    // [button] — extended with Riode variants.
    // ---------------------------------------------------------------------

    private function registerButton(): void
    {
        $this->register('button', function ($attrs, $content) {
            $url = $attrs['url'] ?? $attrs['href'] ?? '#';
            $target = $attrs['target'] ?? '_self';
            $id = $attrs['id'] ?? null;

            $validStyles = ['solid', 'outline', 'outline-light', 'gradient', 'link'];
            $validColors = ['default', 'primary', 'secondary', 'alert', 'success', 'dark', 'white', 'blue', 'orange', 'pink', 'green'];
            $validShapes = ['rectangle', 'rounded', 'ellipse'];
            $validSizes = ['sm', 'md', 'normal', 'lg', 'block'];
            $validShadows = ['none', 'sm', 'md', 'lg'];
            $validAnims = ['none', 'slide-left', 'slide-right', 'slide-up', 'slide-down', 'reveal-left', 'reveal-right'];

            // New-style attributes take priority over legacy `class`.
            $hasNewAttrs = isset($attrs['style']) || isset($attrs['color']) || isset($attrs['shape']) || isset($attrs['size']);

            if ($hasNewAttrs) {
                $style = in_array($attrs['style'] ?? 'solid', $validStyles) ? ($attrs['style'] ?? 'solid') : 'solid';
                $color = in_array($attrs['color'] ?? 'primary', $validColors) ? ($attrs['color'] ?? 'primary') : 'primary';
                $shape = in_array($attrs['shape'] ?? 'rounded', $validShapes) ? ($attrs['shape'] ?? 'rounded') : 'rounded';
                $size = in_array($attrs['size'] ?? 'normal', $validSizes) ? ($attrs['size'] ?? 'normal') : 'normal';
                $shadow = in_array($attrs['shadow'] ?? 'none', $validShadows) ? ($attrs['shadow'] ?? 'none') : 'none';
                $anim = in_array($attrs['animation'] ?? 'none', $validAnims) ? ($attrs['animation'] ?? 'none') : 'none';
                $iconPos = ($attrs['icon-position'] ?? 'right') === 'left' ? 'left' : 'right';
                $icon = $attrs['icon'] ?? null;
                $infinite = isset($attrs['infinite']) && $attrs['infinite'] === 'true';
                $disabled = isset($attrs['disabled']) && $attrs['disabled'] === 'true';
                $underline = $attrs['underline'] ?? 'none';
                $extra = $attrs['class'] ?? '';

                $classes = ['btn'];

                match ($style) {
                    'solid' => $classes[] = 'btn-'.$color,
                    'outline' => array_push($classes, 'btn-outline', 'btn-'.$color),
                    'outline-light' => array_push($classes, 'btn-outline', 'btn-outline-light', 'btn-'.$color),
                    'gradient' => array_push($classes, 'btn-gradient', 'btn-'.$color),
                    'link' => array_push($classes, 'btn-link', 'btn-'.$color),
                    default => null,
                };

                if ($shape === 'rounded') {
                    $classes[] = 'btn-rounded';
                } elseif ($shape === 'ellipse') {
                    $classes[] = 'btn-ellipse';
                }

                if (in_array($size, ['sm', 'md', 'lg'])) {
                    $classes[] = 'btn-'.$size;
                } elseif ($size === 'block') {
                    $classes[] = 'btn-block';
                }

                if ($shadow !== 'none') {
                    $classes[] = $shadow === 'md' ? 'btn-shadow' : 'btn-shadow-'.$shadow;
                }

                if ($icon) {
                    $classes[] = 'btn-icon-'.$iconPos;
                }

                if ($anim !== 'none') {
                    $classes[] = 'btn-'.$anim;
                }

                if ($infinite && $anim !== 'none') {
                    $classes[] = 'btn-infinite';
                }

                if ($style === 'link' && $underline !== 'none') {
                    $classes[] = 'btn-underline';
                }

                if ($disabled) {
                    $classes[] = 'btn-disabled';
                }

                if ($extra) {
                    $classes[] = htmlspecialchars($extra);
                }

                $classStr = implode(' ', $classes);

                $iconHtml = $icon ? sprintf('<i class="%s" aria-hidden="true"></i>', htmlspecialchars($icon)) : '';
                $innerLeft = ($icon && $iconPos === 'left') ? $iconHtml.' ' : '';
                $innerRight = ($icon && $iconPos === 'right') ? ' '.$iconHtml : '';

                $ariaDisabled = $disabled ? ' aria-disabled="true" tabindex="-1"' : '';
                $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
                $idAttr = $id ? sprintf(' id="%s"', htmlspecialchars($id)) : '';

                return sprintf(
                    '<a href="%s" class="%s" target="%s"%s%s%s>%s<span>%s</span>%s</a>',
                    htmlspecialchars($url),
                    $classStr,
                    htmlspecialchars($target),
                    $rel,
                    $ariaDisabled,
                    $idAttr,
                    $innerLeft,
                    $content,
                    $innerRight
                );
            }

            // Legacy path: [button url="#" class="btn-primary"]Text[/button]
            $class = $attrs['class'] ?? 'btn-primary';
            $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $idAttr = $id ? sprintf(' id="%s"', htmlspecialchars($id)) : '';
            $targetAttr = sprintf(' target="%s"', htmlspecialchars($target));

            return sprintf(
                '<a href="%s" class="btn %s"%s%s%s>%s</a>',
                htmlspecialchars($url),
                htmlspecialchars($class),
                $targetAttr,
                $rel,
                $idAttr,
                $content
            );
        }, [
            'description' => 'Inserta un botón con enlace. Soporta estilos solid, outline, gradient, link con variantes de color, forma, tamaño, sombra, icono y animación.',
            'example' => '[button url="/comprar" style="solid" color="primary" shape="rounded" size="lg" icon="fas fa-arrow-right"]Comprar ahora[/button]',
            'attributes' => [
                'url' => 'URL del enlace (alias: href)',
                'target' => 'Destino del enlace (_self, _blank)',
                'id' => 'ID del elemento HTML',
                'style' => 'Estilo visual: solid | outline | outline-light | gradient | link (default: solid)',
                'color' => 'Color: default | primary | secondary | alert | success | dark | white | blue | orange | pink | green (default: primary)',
                'shape' => 'Forma: rectangle | rounded | ellipse (default: rounded)',
                'size' => 'Tamaño: sm | md | normal | lg | block (default: normal)',
                'shadow' => 'Sombra: none | sm | md | lg (default: none)',
                'icon' => 'Icono Font Awesome 6 (ej: fas fa-arrow-right)',
                'icon-position' => 'Posición del icono: left | right (default: right)',
                'animation' => 'Animación CSS: none | slide-left | slide-right | slide-up | slide-down | reveal-left | reveal-right',
                'infinite' => 'Animación en bucle (requiere animation != none): true | false',
                'underline' => 'Subrayado (solo style=link): none | simple | active | custom',
                'disabled' => 'Deshabilitado: true | false',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [alert] — extended with Riode variants.
    // ---------------------------------------------------------------------

    private function registerAlert(): void
    {
        $this->register('alert', function ($attrs, $content) {
            $validTypes = ['primary', 'success', 'warning', 'danger', 'info', 'dark'];
            $validStyles = ['simple', 'dark', 'light'];
            $validLayouts = ['inline', 'stacked', 'message', 'summary'];
            $iconDefaults = [
                'success' => 'fas fa-check-circle',
                'danger' => 'fas fa-circle-exclamation',
                'warning' => 'fas fa-triangle-exclamation',
                'info' => 'fas fa-circle-info',
            ];

            $type = in_array($attrs['type'] ?? 'primary', $validTypes) ? ($attrs['type'] ?? 'primary') : 'primary';
            $style = in_array($attrs['style'] ?? 'simple', $validStyles) ? ($attrs['style'] ?? 'simple') : 'simple';
            $layout = in_array($attrs['layout'] ?? 'inline', $validLayouts) ? ($attrs['layout'] ?? 'inline') : 'inline';
            $title = $attrs['title'] ?? null;
            $round = isset($attrs['round']) && $attrs['round'] === 'true';
            $buttonText = $attrs['button-text'] ?? null;
            $buttonHref = $attrs['button-href'] ?? '#';
            $linkHref = $attrs['link-href'] ?? null;
            $extra = $attrs['class'] ?? '';

            // Icon resolution: explicit FA class, "auto" uses type default, omit = no icon.
            $iconAttr = $attrs['icon'] ?? null;
            $icon = null;
            if ($iconAttr === 'auto') {
                $icon = $iconDefaults[$type] ?? null;
            } elseif ($iconAttr !== null && $iconAttr !== '') {
                $icon = $iconAttr;
            }

            // Dismissible: keep existing `dismissible` attr + new variant `dismissable`.
            $dismissible = (isset($attrs['dismissible']) && $attrs['dismissible'] === 'true')
                || (isset($attrs['dismissable']) && $attrs['dismissable'] === 'true');

            $classes = ['alert', 'alert-'.$type];

            if ($style === 'simple') {
                $classes[] = 'alert-simple';
            } elseif ($style === 'dark') {
                $classes[] = 'alert-dark';
            } elseif ($style === 'light') {
                $classes[] = 'alert-light';
            }

            if ($layout === 'inline') {
                $classes[] = 'alert-inline';
            } elseif ($layout === 'message') {
                $classes[] = 'alert-message';
            } elseif ($layout === 'summary') {
                array_push($classes, 'alert-summary', 'alert-message', 'alert-inline');
            }

            if ($round) {
                $classes[] = 'alert-round';
            }

            if ($icon) {
                $classes[] = 'alert-icon';
            }

            if ($buttonText) {
                $classes[] = 'alert-btn';
            }

            if ($linkHref && ! $buttonText) {
                $classes[] = 'alert-link';
            }

            if ($dismissible) {
                array_push($classes, 'alert-dismissible', 'fade', 'show');
            }

            if ($extra) {
                $classes[] = htmlspecialchars($extra);
            }

            $classStr = implode(' ', $classes);

            $iconHtml = $icon ? sprintf('<i class="%s" aria-hidden="true"></i>', htmlspecialchars($icon)) : '';
            $titleHtml = $title ? sprintf('<h4 class="alert-title">%s</h4>', htmlspecialchars($title)) : '';
            $btnHtml = $buttonText ? sprintf('<a href="%s" class="btn btn-rounded btn-%s">%s</a>', htmlspecialchars($buttonHref), htmlspecialchars($type), htmlspecialchars($buttonText)) : '';
            $closeHtml = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>' : '';

            return sprintf(
                '<div class="%s" role="alert">%s%s%s%s%s</div>',
                $classStr,
                $iconHtml,
                $titleHtml,
                $content,
                $btnHtml,
                $closeHtml
            );
        }, [
            'description' => 'Muestra un mensaje de alerta con estilo Bootstrap y variantes Riode (simple, dark, light, inline, message, summary).',
            'example' => '[alert type="success" style="simple" title="Guardado" icon="auto" dismissible="true"]Operación completada.[/alert]',
            'attributes' => [
                'type' => 'Tipo semántico: primary | success | warning | danger | info | dark (default: primary)',
                'style' => 'Variante visual: simple | dark | light (default: simple)',
                'layout' => 'Distribución: inline | stacked | message | summary (default: inline)',
                'title' => 'Título visible en negrita antes del mensaje',
                'icon' => 'Icono FA6 (ej: fas fa-check) o "auto" para icono por tipo',
                'round' => 'Bordes redondeados: true | false (default: false)',
                'dismissible' => 'Botón cerrar: true | false (default: false)',
                'button-text' => 'Texto del botón de acción',
                'button-href' => 'URL del botón de acción',
                'link-href' => 'URL de enlace inline (exclusivo con button-text)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [columns] / [column]
    // ---------------------------------------------------------------------

    private function registerColumns(): void
    {
        $this->register('columns', function ($attrs, $content) {
            $count = max(1, min(12, (int) ($attrs['count'] ?? 2)));
            $gap = max(0, min(5, (int) ($attrs['gap'] ?? 3)));

            return sprintf(
                '<div class="row row-cols-1 row-cols-md-%d g-%d">%s</div>',
                $count,
                $gap,
                $content
            );
        }, [
            'description' => 'Crea una cuadrícula de columnas Bootstrap.',
            'example' => '[columns count="3" gap="4"][column]Col 1[/column][column]Col 2[/column][/columns]',
            'attributes' => ['count' => 'Número de columnas (1–12)', 'gap' => 'Espacio entre columnas (0–5)'],
        ]);
    }

    private function registerColumn(): void
    {
        $this->register('column', function ($attrs, $content) {
            $class = $attrs['class'] ?? '';

            return sprintf(
                '<div class="col %s">%s</div>',
                htmlspecialchars($class),
                $content
            );
        }, [
            'description' => 'Columna individual dentro de un shortcode [columns].',
            'example' => '[column class="col-md-6"]Contenido[/column]',
            'attributes' => ['class' => 'Clases CSS adicionales'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [youtube]
    // ---------------------------------------------------------------------

    private function registerYoutube(): void
    {
        $this->register('youtube', function ($attrs, $content) {
            $id = $attrs['id'] ?? '';
            $width = $attrs['width'] ?? '560';
            $height = $attrs['height'] ?? '315';
            $title = $attrs['title'] ?? 'YouTube video player';

            if (empty($id)) {
                return '';
            }

            return sprintf(
                '<div class="ratio ratio-16x9"><iframe width="%s" height="%s" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
                htmlspecialchars($width),
                htmlspecialchars($height),
                htmlspecialchars($id),
                htmlspecialchars($title)
            );
        }, [
            'description' => 'Incrusta un video de YouTube con aspecto 16:9.',
            'example' => '[youtube id="dQw4w9WgXcQ" /]',
            'attributes' => ['id' => 'ID del video de YouTube', 'title' => 'Título descriptivo del video'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [image]
    // ---------------------------------------------------------------------

    private function registerImage(): void
    {
        $this->register('image', function ($attrs, $content) {
            $id = $attrs['id'] ?? '';
            $size = $attrs['size'] ?? 'medium';
            $class = $attrs['class'] ?? 'img-fluid';
            $alt = $attrs['alt'] ?? '';

            if (! ctype_digit((string) $id) || (int) $id <= 0) {
                return '';
            }

            try {
                if (class_exists(Media::class)) {
                    $media = Media::find((int) $id);
                    if ($media) {
                        $url = $media->getUrl($size);
                        $alt = $alt ?: $media->alt_text;

                        return sprintf(
                            '<img src="%s" class="%s" alt="%s" loading="lazy">',
                            htmlspecialchars($url),
                            htmlspecialchars($class),
                            htmlspecialchars((string) $alt)
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Image shortcode error: '.$e->getMessage(), ['id' => $id]);
            }

            return sprintf(
                '<img src="#" class="%s" alt="%s" data-id="%s">',
                htmlspecialchars($class),
                htmlspecialchars((string) $alt),
                htmlspecialchars((string) $id)
            );
        }, [
            'description' => 'Inserta una imagen del gestor de medios.',
            'example' => '[image id="123" size="medium" alt="Descripción" /]',
            'attributes' => ['id' => 'ID del archivo de media', 'size' => 'Tamaño (thumbnail, medium, large)', 'class' => 'Clases CSS', 'alt' => 'Texto alternativo'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [icon] — Font Awesome 6 (sin Bootstrap Icons, sin inline style).
    // ---------------------------------------------------------------------

    private function registerIcon(): void
    {
        $this->register('icon', function ($attrs) {
            $validPrefixes = ['fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands'];
            $validSizes = ['xs', 'sm', 'lg', 'xl', '2xl', '1x', '2x', '3x', '4x', '5x', '6x', '7x', '8x', '9x', '10x'];

            $name = $attrs['name'] ?? 'circle-question';
            $prefix = in_array($attrs['prefix'] ?? 'fas', $validPrefixes) ? ($attrs['prefix'] ?? 'fas') : 'fas';
            $size = $attrs['size'] ?? '';
            $color = $attrs['color'] ?? '';
            $extra = $attrs['class'] ?? '';

            $classes = [$prefix, 'fa-'.$name];

            if (in_array($size, $validSizes, true)) {
                $classes[] = 'fa-'.$size;
            }

            if ($color !== '') {
                $classes[] = 'text-'.$color;
            }

            if ($extra !== '') {
                $classes[] = $extra;
            }

            return sprintf(
                '<i class="%s" aria-hidden="true"></i>',
                htmlspecialchars(implode(' ', $classes))
            );
        }, [
            'description' => 'Inserta un icono Font Awesome 6.',
            'example' => '[icon name="star" prefix="fas" size="2x" color="warning" /]',
            'attributes' => [
                'name' => 'Nombre del icono FA6 sin prefijo (ej: star, arrow-right)',
                'prefix' => 'Familia: fas | far | fab (default: fas)',
                'size' => 'Tamaño FA: xs | sm | lg | xl | 2xl | 1x–10x (opcional)',
                'color' => 'Color Bootstrap (primary, success, warning...)',
                'class' => 'Clases CSS adicionales',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [badge]
    // ---------------------------------------------------------------------

    private function registerBadge(): void
    {
        $this->register('badge', function ($attrs, $content) {
            $type = $attrs['type'] ?? 'primary';
            $pill = isset($attrs['pill']) && $attrs['pill'] === 'true' ? ' rounded-pill' : '';

            return sprintf(
                '<span class="badge bg-%s%s">%s</span>',
                htmlspecialchars($type),
                $pill,
                $content
            );
        }, [
            'description' => 'Muestra una etiqueta (badge) con color Bootstrap.',
            'example' => '[badge type="success" pill="true"]Nuevo[/badge]',
            'attributes' => ['type' => 'Color Bootstrap (primary, success, danger...)', 'pill' => 'Bordes redondeados (true/false)'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [card]
    // ---------------------------------------------------------------------

    private function registerCard(): void
    {
        $this->register('card', function ($attrs, $content) {
            $title = $attrs['title'] ?? '';
            $class = $attrs['class'] ?? '';
            $headerClass = $attrs['header_class'] ?? '';

            $titleHtml = $title ? sprintf('<div class="card-header %s"><h5 class="card-title mb-0">%s</h5></div>', htmlspecialchars($headerClass), htmlspecialchars($title)) : '';

            return sprintf(
                '<div class="card %s">%s<div class="card-body">%s</div></div>',
                htmlspecialchars($class),
                $titleHtml,
                $content
            );
        }, [
            'description' => 'Crea una tarjeta Bootstrap con cabecera opcional.',
            'example' => '[card title="Mi tarjeta" class="mb-3"]Contenido de la tarjeta[/card]',
            'attributes' => ['title' => 'Título de la cabecera', 'class' => 'Clases CSS adicionales', 'header_class' => 'Clases de la cabecera'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [accordion] — extended with Riode variants.
    // ---------------------------------------------------------------------

    private function registerAccordion(): void
    {
        $this->register('accordion', function ($attrs, $content) {
            $validStyles = ['simple', 'boxed', 'dropshadow', 'card-bg', 'background'];
            $validColors = ['default', 'primary', 'secondary'];
            $validIconStyles = ['plus-minus', 'none'];
            $validGutters = ['none', 'sm', 'md', 'lg'];

            $id = $attrs['id'] ?? 'accordion-'.uniqid();
            $style = in_array($attrs['style'] ?? 'boxed', $validStyles) ? ($attrs['style'] ?? 'boxed') : 'boxed';
            $color = in_array($attrs['color'] ?? 'default', $validColors) ? ($attrs['color'] ?? 'default') : 'default';
            $iconStyle = in_array($attrs['icon-style'] ?? 'plus-minus', $validIconStyles) ? ($attrs['icon-style'] ?? 'plus-minus') : 'plus-minus';
            $gutter = in_array($attrs['gutter'] ?? 'md', $validGutters) ? ($attrs['gutter'] ?? 'md') : 'md';
            $cardBorder = isset($attrs['card-border']) && $attrs['card-border'] === 'true';
            $border = isset($attrs['border']) && $attrs['border'] === 'true';
            $multiOpen = isset($attrs['multi-open']) && $attrs['multi-open'] === 'true';
            $extra = $attrs['class'] ?? '';

            $classes = ['accordion'];

            match ($style) {
                'simple' => $classes[] = 'accordion-simple',
                'boxed' => $classes[] = 'accordion-boxed',
                'dropshadow' => array_push($classes, 'accordion-dropshadow', 'accordion-boxed'),
                'card-bg' => array_push($classes, 'accordion-card-bg', 'accordion-boxed'),
                'background' => array_push($classes, 'accordion-background', 'accordion-boxed', 'accordion-icon'),
                default => null,
            };

            if ($iconStyle === 'plus-minus') {
                $classes[] = 'accordion-plus';
            }

            if ($color === 'primary') {
                $classes[] = 'accordion-primary';
            } elseif ($color === 'secondary') {
                $classes[] = 'accordion-secondary';
            }

            if ($gutter !== 'none') {
                $classes[] = 'accordion-gutter-'.$gutter;
            }

            if ($cardBorder) {
                $classes[] = 'accordion-card-border';
            }

            if ($border) {
                $classes[] = 'accordion-border';
            }

            if ($extra) {
                $classes[] = htmlspecialchars($extra);
            }

            $classStr = implode(' ', $classes);

            return sprintf(
                '<div class="%s" id="%s" data-multi-open="%s">%s</div>',
                $classStr,
                htmlspecialchars($id),
                $multiOpen ? 'true' : 'false',
                $content
            );
        }, [
            'description' => 'Acordeón colapsable con variantes visuales Riode (simple, boxed, dropshadow, card-bg, background).',
            'example' => '[accordion style="dropshadow" gutter="sm"][accordion-item title="Pregunta" open="true"]Respuesta[/accordion-item][/accordion]',
            'attributes' => [
                'id' => 'ID único del acordeón (auto-generado si no se indica)',
                'style' => 'Variante visual: simple | boxed | dropshadow | card-bg | background (default: boxed)',
                'color' => 'Color de cards: default | primary | secondary (default: default)',
                'icon-style' => 'Icono toggle: plus-minus | none (default: plus-minus)',
                'gutter' => 'Espacio entre items: none | sm | md | lg (default: md)',
                'card-border' => 'Borde en cada card: true | false (default: false)',
                'border' => 'Borde general: true | false (default: false)',
                'multi-open' => 'Varios items abiertos a la vez: true | false (default: false)',
                'class' => 'Clases CSS extra',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [accordion-item]
    // ---------------------------------------------------------------------

    private function registerAccordionItem(): void
    {
        $this->register('accordion-item', function ($attrs, $content) {
            $title = $attrs['title'] ?? 'Accordion Item';
            $id = $attrs['id'] ?? 'item-'.uniqid();
            $icon = $attrs['icon'] ?? null;

            // Support both `open` (new) and `show` (legacy) attrs.
            $isOpen = (isset($attrs['open']) && $attrs['open'] === 'true')
                || (isset($attrs['show']) && $attrs['show'] === 'true');

            // Legacy Bootstrap parent attr (kept for backwards-compat).
            $parent = $attrs['parent'] ?? null;

            $openClass = $isOpen ? ' expanded' : ' collapsed';
            $linkClass = $isOpen ? 'collapse' : 'expand';
            $ariaExpanded = $isOpen ? 'true' : 'false';
            $parentAttr = $parent ? sprintf(' data-bs-parent="#%s"', htmlspecialchars($parent)) : '';
            $iconHtml = $icon ? sprintf('<i class="%s" aria-hidden="true"></i> ', htmlspecialchars($icon)) : '';

            return sprintf(
                '<div class="card">
                    <div class="card-header">
                        <a href="#%s" class="%s" role="button" aria-expanded="%s" aria-controls="%s"%s>
                            %s%s
                        </a>
                    </div>
                    <div id="%s" class="%s"%s>
                        <div class="card-body">%s</div>
                    </div>
                </div>',
                htmlspecialchars($id),
                $linkClass,
                $ariaExpanded,
                htmlspecialchars($id),
                $parentAttr,
                $iconHtml,
                htmlspecialchars($title),
                htmlspecialchars($id),
                ltrim($openClass),
                $parentAttr,
                $content
            );
        }, [
            'description' => 'Elemento individual de acordeón compatible con variantes Riode.',
            'example' => '[accordion-item title="¿Cómo funciona?" open="true" icon="far fa-heart"]Descripción aquí[/accordion-item]',
            'attributes' => [
                'title' => 'Texto del encabezado del ítem',
                'id' => 'ID único del ítem (auto-generado si no se indica)',
                'open' => 'Abierto al cargar: true | false (default: false)',
                'icon' => 'Icono FA6 al lado del título (ej: far fa-heart)',
                'show' => 'Alias legacy de open: true | false',
                'parent' => 'ID del acordeón padre (Bootstrap compat, opcional)',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [quote]
    // ---------------------------------------------------------------------

    private function registerQuote(): void
    {
        $this->register('quote', function ($attrs, $content) {
            $author = $attrs['author'] ?? '';
            $cite = $attrs['cite'] ?? '';

            $footer = '';
            if ($author || $cite) {
                $authorHtml = $author ? htmlspecialchars($author) : '';
                $citeHtml = $cite ? sprintf('<cite title="%s">%s</cite>', htmlspecialchars($cite), htmlspecialchars($cite)) : '';
                $footer = sprintf('<footer class="blockquote-footer">%s %s</footer>', $authorHtml, $citeHtml);
            }

            return sprintf(
                '<blockquote class="blockquote"><p>%s</p>%s</blockquote>',
                $content,
                $footer
            );
        }, [
            'description' => 'Blockquote con autor y fuente opcionales.',
            'example' => '[quote author="Ada Lovelace" cite="Notas, 1843"]El motor analítico teje patrones algebraicos.[/quote]',
            'attributes' => ['author' => 'Nombre del autor', 'cite' => 'Fuente o título de la obra'],
        ]);
    }

    // ---------------------------------------------------------------------
    // [contact-form] — DEMO (sin envío real; usar [form id="X"]).
    // ---------------------------------------------------------------------

    private function registerContactForm(): void
    {
        $this->register('contact-form', function ($attrs, $content) {
            $title = $attrs['title'] ?? 'Contáctenos';
            $formId = 'contact-form-'.uniqid();

            return sprintf(
                '<div class="contact-form-wrapper my-4 border rounded p-3">
            <div class="small text-muted mb-2"><i class="fas fa-circle-info me-1"></i>Demo sin envío real — usa [form id="X"] para formularios funcionales</div>
            <h3 class="mb-3">%s</h3>
            <form id="%s" class="contact-shortcode-form" onsubmit="return false;" aria-disabled="true">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" placeholder="Tu nombre" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="tu@email.com" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Tu mensaje..." disabled></textarea>
                </div>
                <button type="button" class="btn btn-secondary" disabled>Vista previa (no envía)</button>
            </form>
        </div>',
                htmlspecialchars($title),
                htmlspecialchars($formId)
            );
        }, [
            'description' => 'Formulario de contacto (solo maquetado, sin envío). Usa [form id="X"] del módulo Forms para funcionalidad real.',
            'example' => '[contact-form title="Contáctenos"][/contact-form]',
            'attributes' => ['title' => 'Título del formulario'],
        ]);
    }
}
