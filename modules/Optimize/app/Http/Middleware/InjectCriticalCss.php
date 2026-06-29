<?php

namespace Modules\Optimize\Http\Middleware;

use Modules\Optimize\Services\CriticalCssService;

/**
 * Inyecta el Critical CSS inline en <head> y carga el resto de CSS de
 * forma no bloqueante usando el patrón rel="preload" + media="print".
 *
 * Requiere que previamente se haya generado critical.css con:
 *   php artisan optimize:generate-critical-css
 *
 * Si no existe critical.css, simplemente pasa el buffer sin modificar.
 */
class InjectCriticalCss extends PageSpeed
{
    public function apply(string $buffer): string
    {
        try {
            $service = app(CriticalCssService::class);
            $critical = $service->getInlineContent();
        } catch (\Throwable $e) {
            return $buffer;
        }

        if (empty($critical)) {
            return $buffer;
        }

        // Inyectar <style> con critical CSS justo después de <head>
        $style = '<style id="critical-css">'.trim($critical).'</style>';
        $buffer = preg_replace('/<head(\b[^>]*)>/i', '<head$1>'."\n".$style, $buffer, 1) ?? $buffer;

        // Convertir los <link rel="stylesheet"> a carga no bloqueante
        $buffer = preg_replace_callback(
            '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i',
            function (array $matches): string {
                $href = $matches[1];

                // No modificar el critical.css ni inline styles
                if (str_contains($href, 'critical.css')) {
                    return '';
                }

                return '<link rel="preload" as="style" href="'.htmlspecialchars($href, ENT_QUOTES).'">'."\n".
                       '<link rel="stylesheet" href="'.htmlspecialchars($href, ENT_QUOTES).'" media="print" onload="this.media=\'all\'">'."\n".
                       '<noscript><link rel="stylesheet" href="'.htmlspecialchars($href, ENT_QUOTES).'"></noscript>';
            },
            $buffer
        ) ?? $buffer;

        return $buffer;
    }
}
