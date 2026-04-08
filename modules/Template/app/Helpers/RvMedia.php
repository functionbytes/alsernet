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
     * @param  array  $attributes  Atributos HTML adicionales
     */
    public function image(?string $url, ?string $alt = null, ?string $size = null, array $attributes = []): HtmlString
    {
        $src = $this->getImageUrl($url, $size, false, $this->getDefaultImage());

        $attrs = 'src="'.e($src).'"';
        if ($alt !== null) {
            $attrs .= ' alt="'.e($alt).'"';
        }

        foreach ($attributes as $key => $value) {
            if ($value === false) {
                continue;
            }
            $attrs .= ' '.e($key).'="'.e($value).'"';
        }

        return new HtmlString('<img '.$attrs.'>');
    }
}
