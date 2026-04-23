<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Hace async los stylesheets NO críticos y precarga los críticos.
 *
 * CSS críticos (mantienen carga bloqueante para evitar FOUC/CLS):
 *   bootstrap, styles
 *
 * CSS no críticos (async via media="print" onload):
 *   fontawesome, animate, magnific-popup, mousecursor, owlcarousel, aos
 *
 * Solo precargamos los 2-3 CSS realmente críticos para el above-the-fold.
 * Precargar >5 recursos satura la cola de red y retrasa LCP.
 */
class AsyncStylesheets extends PageSpeed
{
    /** @var string[] HREF fragments considered critical (kept render-blocking) */
    private array $criticalFragments = [
        'bootstrap.min.css',
        'styles.min.css',
        'style.css',
        'rtl.css',
        'modules/forms/css/forms',
        'core/select2/css/select2',
    ];

    public function apply(string $buffer): string
    {
        return preg_replace_callback(
            '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i',
            function (array $matches): string {
                $full = $matches[0];
                $href = $matches[1];

                // Skip critical.css inline injection marker
                if (str_contains($href, 'critical.css')) {
                    return '';
                }

                $isCritical = false;
                foreach ($this->criticalFragments as $fragment) {
                    if (str_contains($href, $fragment)) {
                        $isCritical = true;
                        break;
                    }
                }

                $safeHref = htmlspecialchars(html_entity_decode($href, ENT_QUOTES), ENT_QUOTES);

                // Critical CSS: preload + keep render-blocking (max 2-3 preloads)
                if ($isCritical) {
                    return '<link rel="preload" as="style" href="'.$safeHref.'">'."\n".$full;
                }

                // Non-critical CSS: async load WITHOUT preload to avoid
                // saturating the network queue with non-critical resources.
                return '<link rel="stylesheet" href="'.$safeHref.'" media="print" onload="this.media=\'all\'">'."\n".
                       '<noscript><link rel="stylesheet" href="'.$safeHref.'"></noscript>';
            },
            $buffer
        ) ?? $buffer;
    }
}
