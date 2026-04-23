<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Agrega <link rel="preload" as="image"> para la imagen LCP (hero image)
 * basándose en el contenido de la página. Mejora LCP en un 20-40%.
 */
class PreloadLcpImage extends PageSpeed
{
    public function apply(string $buffer): string
    {
        // Detect hero images (fetchpriority=high) and preload them
        if (preg_match('/<img[^>]+fetchpriority=["\']high["\'][^>]+src=["\']([^"\']+)["\']/i', $buffer, $m)) {
            $preload = '<link rel="preload" as="image" href="'.htmlspecialchars($m[1], ENT_QUOTES).'" fetchpriority="high">';
            $buffer = preg_replace('/<head(\b[^>]*)>/i', '<head$1>'.$preload, $buffer, 1) ?? $buffer;
        }

        // Also preload first swiper/lazy image if no fetchpriority found
        if (! isset($m[1]) && preg_match('/<img[^>]+src=["\']([^"\']+\.(?:webp|jpg|jpeg|png))["\'][^>]*class=["\'][^"\']*header-img/i', $buffer, $m2)) {
            $preload = '<link rel="preload" as="image" href="'.htmlspecialchars($m2[1], ENT_QUOTES).'">';
            $buffer = preg_replace('/<head(\b[^>]*)>/i', '<head$1>'.$preload, $buffer, 1) ?? $buffer;
        }

        return $buffer;
    }
}
