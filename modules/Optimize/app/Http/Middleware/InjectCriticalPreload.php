<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Inyecta `<link rel="preload">` para el primer stylesheet y la primera imagen
 * hero detectada en el HTML (por class hero|banner|slider o data-hero). Esto
 * anticipa la descarga de recursos críticos → mejora LCP en PageSpeed.
 *
 * Efectos colaterales:
 * - No duplica preloads si ya hay uno para el mismo href.
 * - Se limita al primer stylesheet y a la primera imagen hero (LCP candidate).
 */
class InjectCriticalPreload extends PageSpeed
{
    public function apply(string $buffer): string
    {
        $preloads = [];

        // Primer <link rel="stylesheet"> externo → preload antes del <head>.
        if (preg_match('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $buffer, $m)) {
            $href = $m[1];
            if (! str_contains($buffer, 'rel="preload"'.'" href="'.$href)
                && ! preg_match('/<link[^>]+rel=["\']preload["\'][^>]+href=["\']'.preg_quote($href, '/').'["\']/i', $buffer)) {
                $preloads[] = '<link rel="preload" as="style" href="'.htmlspecialchars($href, ENT_QUOTES).'">';
            }
        }

        // Primera imagen con class hero|banner|slider → preload LCP candidate.
        if (preg_match('/<img[^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $buffer, $m)
            || preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\'][^>]*>/i', $buffer, $m)) {
            $src = $m[1];
            if (! preg_match('/<link[^>]+rel=["\']preload["\'][^>]+href=["\']'.preg_quote($src, '/').'["\']/i', $buffer)) {
                $preloads[] = '<link rel="preload" as="image" href="'.htmlspecialchars($src, ENT_QUOTES).'" fetchpriority="high">';
            }
        }

        if (empty($preloads)) {
            return $buffer;
        }

        $injection = implode("\n", $preloads)."\n";

        // Insertar justo después de <head> para que resuelvan lo antes posible.
        return preg_replace('/<head(\b[^>]*)>/i', '<head$1>'.$injection, $buffer, 1) ?? $buffer;
    }
}
