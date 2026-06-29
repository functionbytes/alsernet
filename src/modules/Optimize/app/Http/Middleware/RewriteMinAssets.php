<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Reescribe referencias a `foo.css` / `foo.js` hacia `foo.min.css` /
 * `foo.min.js` cuando el archivo minificado existe en `public/`. Se
 * acompaña del comando `optimize:minify-theme-assets` que los genera.
 *
 * Ataca "Minifica los archivos CSS" + "Minifica los recursos JavaScript"
 * en PageSpeed.
 */
class RewriteMinAssets extends PageSpeed
{
    public function apply(string $buffer): string
    {
        $buffer = preg_replace_callback(
            '/(<link[^>]+href=["\'])([^"\']+\.css)(\?[^"\']*)?(["\'])/i',
            fn (array $m) => $this->rewriteAssetRef($m, 'css'),
            $buffer
        ) ?? $buffer;

        return preg_replace_callback(
            '/(<script[^>]+src=["\'])([^"\']+\.js)(\?[^"\']*)?(["\'])/i',
            fn (array $m) => $this->rewriteAssetRef($m, 'js'),
            $buffer
        ) ?? $buffer;
    }

    /** @param  array<int,string>  $m */
    private function rewriteAssetRef(array $m, string $ext): string
    {
        [$full, $prefix, $url, $query, $quote] = [$m[0], $m[1], $m[2], $m[3] ?? '', $m[4]];

        // Ya es .min → deja.
        if (str_contains($url, '.min.'.$ext)) {
            return $full;
        }

        // Solo rutas locales (absolutas o relativas al host).
        $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';
        $parsed = parse_url($url);
        if (isset($parsed['host']) && $host && $parsed['host'] !== $host) {
            return $full;
        }

        $minUrl = preg_replace('/\.'.$ext.'$/', '.min.'.$ext, $url);
        if (! is_string($minUrl)) {
            return $full;
        }

        $path = ltrim((string) parse_url($minUrl, PHP_URL_PATH), '/');
        if (! is_file(public_path($path))) {
            return $full;
        }

        return $prefix.$minUrl.$query.$quote;
    }
}
