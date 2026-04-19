<?php

namespace Modules\Template\Helpers;

use Illuminate\Support\HtmlString;

class RvMedia
{
    /**
     * Devuelve la URL de una imagen.
     * En inoqualabs las imágenes se almacenan como URLs absolutas.
     */
    public function getImageUrl(?string $url, ?string $size = null, bool $hasDefaultImage = false, ?string $default = null): string
    {
        if (! empty($url)) {
            return $url;
        }

        return $default ?? '';
    }

    /**
     * Devuelve la URL de la imagen por defecto del sitio.
     */
    public function getDefaultImage(): string
    {
        return asset('images/no-image.png');
    }

    /**
     * Renderiza un tag <img> para la imagen indicada.
     *
     * Si existe una versión `.webp` junto al original (generada por el
     * comando `media:convert-webp`), emite un `<picture>` con `<source>`
     * para que el navegador elija WebP cuando lo soporta, con fallback al
     * original. Esto reduce entre 25 y 35 % el peso de imágenes (uno de
     * los mayores ahorros que reporta PageSpeed).
     *
     * Extras soportados vía $attributes:
     *   - loading: "lazy" | "eager"
     *   - fetchpriority: "high" | "low" | "auto"
     *   - srcset / sizes: passthrough para responsive images
     *   - width / height: recomendado para evitar CLS
     *
     * @param  array  $attributes  Atributos HTML adicionales
     */
    public function image(?string $url, ?string $alt = null, ?string $size = null, array $attributes = []): HtmlString
    {
        $src = $this->getImageUrl($url, $size, false, $this->getDefaultImage());

        $imgAttrs = 'src="'.e($src).'"';
        $imgAttrs .= ' alt="'.e($alt ?? '').'"';

        foreach ($attributes as $key => $value) {
            if ($value === false || $value === null) {
                continue;
            }
            $imgAttrs .= ' '.e($key).'="'.e((string) $value).'"';
        }

        $imgTag = '<img '.$imgAttrs.'>';

        // Si hay variantes `-480w.webp`, `-768w.webp`, etc. generadas por
        // `media:generate-srcset`, emitimos `<source srcset>` responsive
        // para que mobile no descargue la versión full-HD. El primer
        // `<source>` es el responsive; el segundo es el .webp simple como
        // fallback; el `<img>` queda como último fallback universal.
        $responsiveSrcset = $this->buildResponsiveSrcset($src);
        $webpSimple = $this->deriveWebpUrl($src);

        if ($responsiveSrcset !== null || $webpSimple !== null) {
            $sources = '';
            if ($responsiveSrcset !== null) {
                $sources .= '<source type="image/webp" srcset="'.e($responsiveSrcset).'" '
                    .'sizes="(max-width: 768px) 100vw, (max-width: 1200px) 75vw, 50vw">';
            } elseif ($webpSimple !== null) {
                $sources .= '<source type="image/webp" srcset="'.e($webpSimple).'">';
            }

            return new HtmlString('<picture>'.$sources.$imgTag.'</picture>');
        }

        return new HtmlString($imgTag);
    }

    /**
     * Construye el atributo srcset con las variantes `-{W}w.webp` que
     * existan en `public/`. Devuelve null si no encuentra ninguna.
     */
    private function buildResponsiveSrcset(string $src): ?string
    {
        if (! preg_match('/\.(jpe?g|png)(\?.*)?$/i', $src)) {
            return null;
        }
        $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';
        $parsed = parse_url($src);
        if (isset($parsed['host']) && $host && $parsed['host'] !== $host) {
            return null;
        }

        $widths = [480, 768, 1024, 1920];
        $entries = [];

        foreach ($widths as $w) {
            $variantUrl = preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '-'.$w.'w.webp$2', $src);
            if (! is_string($variantUrl)) {
                continue;
            }
            $path = ltrim((string) parse_url($variantUrl, PHP_URL_PATH), '/');
            if (is_file(public_path($path))) {
                $entries[] = $variantUrl.' '.$w.'w';
            }
        }

        return $entries === [] ? null : implode(', ', $entries);
    }

    /**
     * Deriva la URL .webp candidata si el src es un JPEG/PNG local y el
     * archivo .webp hermano existe en `public/`. Devuelve null en caso
     * contrario.
     */
    private function deriveWebpUrl(string $src): ?string
    {
        if (! preg_match('/\.(jpe?g|png)(\?.*)?$/i', $src, $m)) {
            return null;
        }
        // Solo archivos locales.
        $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';
        $parsed = parse_url($src);
        if (isset($parsed['host']) && $host && $parsed['host'] !== $host) {
            return null;
        }

        $webpUrl = preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $src);
        if (! is_string($webpUrl)) {
            return null;
        }

        $path = ltrim((string) parse_url($webpUrl, PHP_URL_PATH), '/');
        $fsPath = public_path($path);

        return is_file($fsPath) ? $webpUrl : null;
    }
}
