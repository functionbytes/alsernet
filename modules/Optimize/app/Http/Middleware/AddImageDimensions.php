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
            '/<img(?![^>]*\bwidth=)(?![^>]*\bheight=)([^>]*\bsrc=["\']([^"\']+)["\'][^>]*)>/i',
            function (array $m): string {
                $attrs = $m[1];
                $src = $m[2];

                [$w, $h] = $this->resolveDimensions($src);
                if (! $w || ! $h) {
                    return $m[0];
                }

                return '<img width="'.$w.'" height="'.$h.'"'.$attrs.'>';
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
            $info = @getimagesize($path);
            if (! $info) {
                return [null, null];
            }

            return [(int) $info[0], (int) $info[1]];
        });
    }

    /** Map a URL or relative path to a readable file in the public dir. */
    private function pathForUrl(string $src): ?string
    {
        // Data URIs are skipped.
        if (str_starts_with($src, 'data:')) {
            return null;
        }

        $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';
        $parsed = parse_url($src);

        // External host → skip to avoid network I/O.
        if (isset($parsed['host']) && $host && $parsed['host'] !== $host) {
            return null;
        }

        $path = $parsed['path'] ?? $src;
        $path = ltrim($path, '/');

        $candidate = public_path($path);

        return is_file($candidate) ? $candidate : null;
    }
}
