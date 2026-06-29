<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Injects `font-display: swap` into every `@font-face` block that does not
 * already declare a `font-display`. Eliminates FOIT (Flash of Invisible Text)
 * during webfont loading — directly improves LCP / CLS on PageSpeed.
 *
 * Also rewrites Google Fonts URLs to append `&display=swap` when missing.
 */
class InjectFontDisplay extends PageSpeed
{
    public function apply(string $buffer): string
    {
        // 1) Inline @font-face blocks: append `font-display: swap;` before the
        //    closing brace when none is declared.
        $buffer = preg_replace_callback(
            '/@font-face\s*\{([^}]*)\}/i',
            function (array $m): string {
                $body = $m[1];
                if (preg_match('/\bfont-display\s*:/i', $body)) {
                    return $m[0];
                }

                return '@font-face{'.rtrim($body).';font-display:swap;}';
            },
            $buffer
        );

        // 2) Google Fonts URLs in <link> tags: ensure display=swap is present.
        $buffer = preg_replace_callback(
            '/<link[^>]+href=["\']([^"\']*fonts\.googleapis\.com\/css2?[^"\']*)["\'][^>]*>/i',
            function (array $m): string {
                [$full, $href] = $m;
                if (str_contains($href, 'display=')) {
                    return $full;
                }
                $sep = str_contains($href, '?') ? '&' : '?';
                $newHref = $href.$sep.'display=swap';

                return str_replace($href, $newHref, $full);
            },
            $buffer
        );

        return $buffer;
    }
}
