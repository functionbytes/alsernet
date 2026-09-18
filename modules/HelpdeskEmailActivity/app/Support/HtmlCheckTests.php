<?php

namespace Modules\HelpdeskEmailActivity\Support;

/**
 * Catálogo de comprobaciones del inspector de compatibilidad: qué buscar en el
 * HTML de un correo y a qué feature de caniemail.com corresponde cada hallazgo.
 *
 * Las claves son slugs del dataset (ver CanIEmailDataset); los valores, o bien
 * un selector CSS (se resuelve con symfony/css-selector sobre DOMXPath), o bien
 * una expresión regular PCRE.
 *
 * Están separadas por CÓMO se detectan, no por categoría, porque cada grupo se
 * evalúa sobre una fuente distinta: el árbol DOM, el atributo style="" de cada
 * nodo, o el texto de los bloques <style>.
 */
class HtmlCheckTests
{
    /**
     * Elementos y atributos HTML — selector CSS por slug.
     *
     * "html-body" es especial: <body> siempre existe en un documento parseado
     * (DOMDocument lo inventa aunque el correo sea un fragmento), así que se
     * comprueba con una regex sobre la fuente, no con el selector.
     *
     * @return array<string, string>
     */
    public static function html(): array
    {
        return [
            'html-body' => 'body',
            'html-object' => 'object, embed, image, pdf',
            'html-link' => 'link',
            'html-hr' => 'hr',
            'html-dialog' => 'dialog',
            'html-srcset' => '[srcset]',
            'html-picture' => 'picture',
            'html-svg' => 'svg',
            'html-progress' => 'progress',
            'html-required' => '[required]',
            'html-meter' => 'meter',
            'html-audio' => 'audio',
            'html-form' => 'form',
            'html-input-submit' => 'submit',
            'html-button-reset' => 'button[type="reset"]',
            'html-button-submit' => 'submit, button[type="submit"]',
            'html-base' => 'base',
            'html-input-checkbox' => 'checkbox',
            'html-input-hidden' => '[type="hidden"]',
            'html-input-radio' => 'radio',
            'html-input-text' => 'input[type="text"]',
            'html-video' => 'video',
            'html-semantics' => 'article, aside, details, figcaption, figure, footer, header, main, mark, nav, section, summary, time',
            'html-select' => 'select',
            'html-textarea' => 'textarea',
            'html-anchor-links' => 'a[href^="#"]',
            'html-style' => 'style',
            'html-image-maps' => 'map, img[usemap]',
        ];
    }

    /**
     * Formato de imagen, por la extensión del src de cada <img>.
     *
     * @return array<string, string>
     */
    public static function imageRegex(): array
    {
        return [
            'image-apng' => '~\.apng$~i',
            'image-avif' => '~\.avif$~i',
            'image-base64' => '~^data:image/~i',
            'image-bmp' => '~\.bmp$~i',
            'image-gif' => '~\.gif$~i',
            'image-hdr' => '~\.hdr$~i',
            'image-heif' => '~\.heif$~i',
            'image-ico' => '~\.ico$~i',
            'image-mp4' => '~\.mp4$~i',
            'image-ppm' => '~\.ppm$~i',
            'image-svg' => '~\.svg$~i',
            'image-tiff' => '~\.tiff?$~i',
            'image-webp' => '~\.webp$~i',
        ];
    }

    /**
     * Atributos de presentación heredados de la era pre-CSS (bgcolor, border…),
     * que caniemail evalúa bajo la misma feature que su equivalente CSS.
     *
     * @return array<string, string>
     */
    public static function styleAttributes(): array
    {
        return [
            'css-background-color' => '[bgcolor]',
            'css-background' => '[background]',
            'css-border' => '[border]',
            'css-height' => '[height]',
            'css-padding' => '[padding]',
            'css-width' => '[width]',
        ];
    }

    /**
     * Propiedades CSS buscadas dentro del atributo style="" de cada nodo.
     *
     * @return array<string, string>
     */
    public static function cssInlineRegex(): array
    {
        return [
            'css-accent-color' => '~(^|\s|;)accent-color(\s+)?:~i',
            'css-align-items' => '~(^|\s|;)align-items(\s+)?:~i',
            'css-aspect-ratio' => '~(^|\s|;)aspect-ratio(\s+)?:~i',
            'css-background-blend-mode' => '~(^|\s|;)background-blend-mode(\s+)?:~i',
            'css-background-clip' => '~(^|\s|;)background-clip(\s+)?:~i',
            'css-background-color' => '~(^|\s|;)background-color(\s+)?:~i',
            'css-background-image' => '~(^|\s|;)background-image(\s+)?:~i',
            'css-background-origin' => '~(^|\s|;)background-origin(\s+)?:~i',
            'css-background-position' => '~(^|\s|;)background-position(\s+)?:~i',
            'css-background-repeat' => '~(^|\s|;)background-repeat(\s+)?:~i',
            'css-background-size' => '~(^|\s|;)background-size(\s+)?:~i',
            'css-background' => '~(^|\s|;)background(\s+)?:~i',
            'css-block-inline-size' => '~(^|\s|;)block-inline-size(\s+)?:~i',
            'css-border-image' => '~(^|\s|;)border-image(\s+)?:~i',
            'css-border-inline-block-individual' => '~(^|\s|;)border-inline(\s+)?:~i',
            'css-border-radius' => '~(^|\s|;)border-radius(\s+)?:~i',
            'css-border' => '~(^|\s|;)border(\s+)?:~i',
            'css-box-shadow' => '~(^|\s|;)box-shadow(\s+)?:~i',
            'css-box-sizing' => '~(^|\s|;)box-sizing(\s+)?:~i',
            'css-caption-side' => '~(^|\s|;)caption-side(\s+)?:~i',
            'css-clip-path' => '~(^|\s|;)clip-path(\s+)?:~i',
            'css-column-count' => '~(^|\s|;)column-count(\s+)?:~i',
            'css-column-layout-properties' => '~(^|\s|;)column-layout-properties(\s+)?:~i',
            'css-conic-gradient' => '~(^|\s|;)conic-gradient(\s+)?:~i',
            'css-direction' => '~(^|\s|;)direction(\s+)?:~i',
            'css-display-flex' => '~(^|\s|;)display(\s+)?:(\s+)?flex($|\s|;)~i',
            'css-display-grid' => '~(^|\s|;)display:grid~i',
            'css-display-none' => '~(^|\s|;)display:none~i',
            'css-display' => '~(^|\s|;)display(\s+)?:~i',
            'css-filter' => '~(^|\s|;)filter(\s+)?:~i',
            'css-flex-direction' => '~(^|\s|;)flex-direction(\s+)?:~i',
            'css-flex-wrap' => '~(^|\s|;)flex-wrap(\s+)?:~i',
            'css-float' => '~(^|\s|;)float(\s+)?:~i',
            'css-font-kerning' => '~(^|\s|;)font-kerning(\s+)?:~i',
            'css-font-weight' => '~(^|\s|;)font-weight(\s+)?:~i',
            'css-font' => '~(^|\s|;)font(\s+)?:~i',
            'css-gap' => '~(^|\s|;)gap(\s+)?:~i',
            'css-grid-template' => '~(^|\s|;)grid-template(\s+)?:~i',
            'css-height' => '~(^|\s|;)height(\s+)?:~i',
            'css-hyphens' => '~(^|\s|;)hyphens(\s+)?:~i',
            'css-important' => '~!important($|\s|;)~i',
            'css-inline-size' => '~(^|\s|;)inline-size(\s+)?:~i',
            'css-intrinsic-size' => '~(^|\s|;)intrinsic-size(\s+)?:~i',
            'css-justify-content' => '~(^|\s|;)justify-content(\s+)?:~i',
            'css-letter-spacing' => '~(^|\s|;)letter-spacing(\s+)?:~i',
            'css-line-height' => '~(^|\s|;)line-height(\s+)?:~i',
            'css-list-style-image' => '~(^|\s|;)list-style-image(\s+)?:~i',
            'css-list-style-position' => '~(^|\s|;)list-style-position(\s+)?:~i',
            'css-list-style' => '~(^|\s|;)list-style(\s+)?:~i',
            'css-margin-block-start-end' => '~(^|\s|;)margin-block-(start|end)(\s+)?:~i',
            'css-margin-inline-block' => '~(^|\s|;)margin-inline-block(\s+)?:~i',
            'css-margin-inline-start-end' => '~(^|\s|;)margin-inline-(start|end)(\s+)?:~i',
            'css-margin-inline' => '~(^|\s|;)margin-inline(\s+)?:~i',
            'css-margin' => '~(^|\s|;)margin(\s+)?:~i',
            'css-max-block-size' => '~(^|\s|;)max-block-size(\s+)?:~i',
            'css-max-height' => '~(^|\s|;)max-height(\s+)?:~i',
            'css-max-width' => '~(^|\s|;)max-width(\s+)?:~i',
            'css-min-height' => '~(^|\s|;)min-height(\s+)?:~i',
            'css-min-inline-size' => '~(^|\s|;)min-inline-size(\s+)?:~i',
            'css-min-width' => '~(^|\s|;)min-width(\s+)?:~i',
            'css-mix-blend-mode' => '~(^|\s|;)mix-blend-mode(\s+)?:~i',
            'css-modern-color' => '~(^|\s|;)modern-color(\s+)?:~i',
            'css-object-fit' => '~(^|\s|;)object-fit(\s+)?:~i',
            'css-object-position' => '~(^|\s|;)object-position(\s+)?:~i',
            'css-opacity' => '~(^|\s|;)opacity(\s+)?:~i',
            'css-outline-offset' => '~(^|\s|;)outline-offset(\s+)?:~i',
            'css-outline' => '~(^|\s|;)outline(\s+)?:~i',
            'css-overflow-wrap' => '~(^|\s|;)overflow-wrap(\s+)?:~i',
            'css-overflow' => '~(^|\s|;)overflow(\s+)?:~i',
            'css-padding-block-start-end' => '~(^|\s|;)padding-block-(start|end)(\s+)?:~i',
            'css-padding-inline-block' => '~(^|\s|;)padding-inline-block(\s+)?:~i',
            'css-padding-inline-start-end' => '~(^|\s|;)padding-inline-(start|end)(\s+)?:~i',
            'css-padding' => '~(^|\s|;)padding(\s+)?:~i',
            'css-position' => '~(^|\s|;)position(\s+)?:~i',
            'css-radial-gradient' => '~(^|\s|;)radial-gradient(\s+)?:~i',
            'css-rgb' => '~(\s|:)rgb\(~i',
            'css-rgba' => '~(\s|:)rgba\(~i',
            'css-scroll-snap' => '~(^|\s|;)roll-snap(\s+)?:~i',
            'css-tab-size' => '~(^|\s|;)tab-size(\s+)?:~i',
            'css-table-layout' => '~(^|\s|;)table-layout(\s+)?:~i',
            'css-text-align-last' => '~(^|\s|;)text-align-last(\s+)?:~i',
            'css-text-align' => '~(^|\s|;)text-align(\s+)?:~i',
            'css-text-decoration-color' => '~(^|\s|;)text-decoration-color(\s+)?:~i',
            'css-text-decoration-thickness' => '~(^|\s|;)text-decoration-thickness(\s+)?:~i',
            'css-text-decoration' => '~(^|\s|;)text-decoration(\s+)?:~i',
            'css-text-emphasis-position' => '~(^|\s|;)text-emphasis-position(\s+)?:~i',
            'css-text-emphasis' => '~(^|\s|;)text-emphasis(\s+)?:~i',
            'css-text-indent' => '~(^|\s|;)text-indent(\s+)?:~i',
            'css-text-overflow' => '~(^|\s|;)text-overflow(\s+)?:~i',
            'css-text-shadow' => '~(^|\s|;)text-shadow(\s+)?:~i',
            'css-text-transform' => '~(^|\s|;)text-transform(\s+)?:~i',
            'css-text-underline-offset' => '~(^|\s|;)text-underline-offset(\s+)?:~i',
            'css-transform' => '~(^|\s|;)transform(\s+)?:~i',
            'css-unit-calc' => '~(\s|:)calc\(~i',
            'css-variables' => '~(^|\s|;)variables(\s+)?:~i',
            'css-visibility' => '~(^|\s|;)visibility(\s+)?:~i',
            'css-white-space' => '~(^|\s|;)white-space(\s+)?:~i',
            'css-width' => '~(^|\s|;)width(\s+)?:~i',
            'css-word-break' => '~(^|\s|;)word-break(\s+)?:~i',
            'css-writing-mode' => '~(^|\s|;)writing-mode(\s+)?:~i',
            'css-z-index' => '~(^|\s|;)z-index(\s+)?:~i',
        ];
    }

    /**
     * Unidades y valores especiales, buscados en el conjunto de atributos
     * style="" del documento.
     *
     * @return array<string, string>
     */
    public static function cssUnitRegex(): array
    {
        return [
            'css-unit-ch' => '~\b\d+ch\b~',
            'css-unit-initial' => '~:\s?initial\b~',
            'css-unit-rem' => '~\b\d+rem\b~',
            'css-unit-vh' => '~\b\d+vh\b~',
            'css-unit-vmax' => '~\b\d+vmax\b~',
            'css-unit-vmin' => '~\b\d+vmin\b~',
            'css-unit-vw' => '~\b\d+vw\b~',
        ];
    }

    /**
     * At-rules y pseudo-selectores: solo existen dentro de bloques <style>,
     * nunca en un atributo style="", así que se buscan sobre el CSS en bruto.
     *
     * @return array<string, string>
     */
    public static function cssBlockRegex(): array
    {
        return [
            'css-at-font-face' => '~@font-face\s+?{~mi',
            'css-at-import' => '~@import\s~mi',
            'css-at-keyframes' => '~@keyframes\s~mi',
            'css-at-media' => '~@media\s?\(~mi',
            'css-at-supports' => '~@supports\s?\(~mi',
            'css-pseudo-class-active' => '~:active~',
            'css-pseudo-class-checked' => '~:checked~',
            'css-pseudo-class-first-child' => '~:first-child~',
            'css-pseudo-class-first-of-type' => '~:first-of-type~',
            'css-pseudo-class-focus' => '~:focus~',
            'css-pseudo-class-has' => '~:has~',
            'css-pseudo-class-hover' => '~:hover~',
            'css-pseudo-class-lang' => '~:lang\s?\(~',
            'css-pseudo-class-last-child' => '~:last-child~',
            'css-pseudo-class-last-of-type' => '~:last-of-type~',
            'css-pseudo-class-link' => '~:link~',
            'css-pseudo-class-not' => '~:not(\s+)?\(~',
            'css-pseudo-class-nth-child' => '~:nth-child(\s+)?\(~',
            'css-pseudo-class-nth-last-child' => '~:nth-last-child(\s+)?\(~',
            'css-pseudo-class-nth-last-of-type' => '~:nth-last-of-type(\s+)?\(~',
            'css-pseudo-class-nth-of-type' => '~:nth-of-type(\s+)?\(~',
            'css-pseudo-class-only-child' => '~:only-child(\s+)?\(~',
            'css-pseudo-class-only-of-type' => '~:only-of-type(\s+)?\(~',
            'css-pseudo-class-target' => '~:target~',
            'css-pseudo-class-visited' => '~:visited~',
            'css-pseudo-element-after' => '~:after~',
            'css-pseudo-element-before' => '~:before~',
            'css-pseudo-element-first-letter' => '~::first-letter~',
            'css-pseudo-element-first-line' => '~::first-line~',
            'css-pseudo-element-marker' => '~::marker~',
            'css-pseudo-element-placeholder' => '~::placeholder~',
        ];
    }
}
