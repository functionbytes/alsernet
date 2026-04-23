<?php

namespace Modules\Optimize\Http\Middleware;

use Illuminate\Support\Facades\Cache;

/**
 * Inyecta `width` y `height` en `<img>` que no los declaran y cuyas URLs
 * apunten a archivos locales servibles desde `public/`. Reduce el
 * Cumulative Layout Shift (CLS) — métrica directa en PageSpeed.
 *
 * Para evitar costo en cada request, cachea las dimensiones por URL un año.
 */
class AddImageDimensions extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $m): string {
                $attrs = $m[1];
                $full = $m[0];

                // Already has both width and height
                if (preg_match('/\bwidth\s*=\s*["\']?[^"\'>\s]+["\']?/i', $attrs)
                    && preg_match('/\bheight\s*=\s*["\']?[^"\'>\s]+["\']?/i', $attrs)) {
                    return $full;
                }

                // Extract src
                if (! preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                    return $full;
                }
                $src = $srcMatch[1];

                [$w, $h] = $this->resolveDimensions($src);
                if (! $w || ! $h) {
                    return $full;
                }

                $hasWidth = preg_match('/\bwidth\s*=\s*["\']?([^"\'>\s]+)["\']?/i', $attrs, $wMatch);
                $hasHeight = preg_match('/\bheight\s*=\s*["\']?([^"\'>\s]+)["\']?/i', $attrs, $hMatch);

                if (! $hasWidth && ! $hasHeight) {
                    // Insert both before src
                    return '<img width="'.$w.'" height="'.$h.'"'.$attrs.'>';
                }

                if ($hasWidth && ! $hasHeight) {
                    $width = (int) $wMatch[1];
                    $computedHeight = (int) round($width * ($h / $w));

                    return str_replace($wMatch[0], $wMatch[0].' height="'.$computedHeight.'"', $full);
                }

                if (! $hasWidth && $hasHeight) {
                    $height = (int) $hMatch[1];
                    $computedWidth = (int) round($height * ($w / $h));

                    return str_replace($hMatch[0], 'width="'.$computedWidth.'" '.$hMatch[0], $full);
                }

                return $full;
            },
            $buffer
        ) ?? $buffer;
    }

    /** @return array{0:int|null,1:int|null} */
    private function resolveDimensions(string $src): array
    {
        $cacheKey = 'optimize.img.dim:'.sha1($src);

        return Cache::remember($cacheKey, now()->addYear(), function () use ($src) {
            $path = $this->pathForUrl($src);
            if (! $path || ! is_file($path)) {
                return [null, null];
            }

            if (str_ends_with(strtolower($path), '.svg')) {
                return $this->resolveSvgDimensions($path);
            }

            $info = @getimagesize($path);
            if (! $info) {
                return [null, null];
            }

            return [(int) $info[0], (int) $info[1]];
        });
    }

    /** @return array{0:int|null,1:int|null} */
    private function resolveSvgDimensions(string $path): array
    {
        $svg = @file_get_contents($path);
        if (! $svg) {
            return [null, null];
        }

        if (preg_match('/viewBox=["\']\s*[\d\.]+\s+[\d\.]+\s+([\d\.]+)\s+([\d\.]+)\s*["\']/i', $svg, $m)) {
            $w = (float) $m[1];
            $h = (float) $m[2];
            if ($w > 0 && $h > 0) {
                return [(int) round($w), (int) round($h)];
            }
        }

        if (preg_match('/<svg[^>]*\swidth=["\']([\d\.]+)(?:px)?["\']/i', $svg, $mW)
            && preg_match('/<svg[^>]*\sheight=["\']([\d\.]+)(?:px)?["\']/i', $svg, $mH)) {
            $w = (float) $mW[1];
            $h = (float) $mH[1];
            if ($w > 0 && $h > 0) {
                return [(int) round($w), (int) round($h)];
            }
        }

        return [null, null];
    }

    /** Map a URL or relative path to a readable file in the public dir. */
    private function pathForUrl(string $src): ?string
    {
        if (str_starts_with($src, 'data:')) {
            return null;
        }

        $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';
        $parsed = parse_url($src);

        if (isset($parsed['host']) && $host && $parsed['host'] !== $host) {
            return null;
        }

        $path = $parsed['path'] ?? $src;
        $path = ltrim($path, '/');

        $candidate = public_path($path);

        return is_file($candidate) ? $candidate : null;
    }
}
