<?php

namespace Modules\Template\Helpers;

use Illuminate\Support\HtmlString;

class BaseHelper
{
    /**
     * Carga una fuente de Google Fonts con una etiqueta <link>.
     */
    public function googleFonts(string $font): HtmlString
    {
        return new HtmlString('<link rel="stylesheet" href="'.e($font).'">');
    }

    /**
     * Indica si el sitio tiene dirección RTL activada.
     */
    public function isRtlEnabled(): bool
    {
        return setting('locale_direction', 'ltr') === 'rtl';
    }

    /**
     * Devuelve la URL de la página principal.
     */
    public function getHomepageUrl(): string
    {
        return url('/');
    }

    /**
     * Renderiza un icono a partir de su clase CSS (p.ej. "fa fa-phone").
     * Soporta clases Font Awesome y cualquier librería de iconos basada en clases.
     *
     * @param  string  $name  Clase(s) CSS del icono, p.ej. "fa fa-phone"
     * @param  string|null  $size  Tamaño opcional (no se usa en FA, se ignora)
     * @param  array  $attributes  Atributos HTML adicionales
     */
    public function renderIcon(string $name, ?string $size = null, array $attributes = []): string
    {
        if (empty($name)) {
            return '';
        }

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' '.e($key).'="'.e($value).'"';
        }

        return '<i class="'.e($name).'"'.$attrs.'></i>';
    }

    /**
     * Sanitiza HTML eliminando etiquetas peligrosas.
     * Usa HTMLPurifier si está disponible, de lo contrario escapa el HTML.
     */
    public function clean(array|string|null $dirty, array|string|null $config = null): array|string|null
    {
        if ($dirty === null || $dirty === '') {
            return $dirty;
        }

        if (is_array($dirty)) {
            return array_map(fn ($item) => $this->clean($item, $config), $dirty);
        }

        // Usar HTMLPurifier si está disponible (vía mews/purifier)
        if (class_exists(\HTMLPurifier::class)) {
            $purifier = new \HTMLPurifier;

            return $purifier->purify($dirty);
        }

        // Fallback: strip_tags preservando etiquetas seguras
        return strip_tags((string) $dirty, '<b><strong><i><em><u><a><p><br><ul><ol><li><span><div><h1><h2><h3><h4><h5><h6>');
    }

    /**
     * Limpia y devuelve como HtmlString (para uso con {!! !!}).
     */
    public function html(array|string|null $dirty, array|string|null $config = null): HtmlString
    {
        return new HtmlString((string) $this->clean($dirty, $config));
    }
}
