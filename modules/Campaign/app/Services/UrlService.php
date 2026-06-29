<?php

namespace Modules\Campaign\Services;

/**
 * URLs de assets de tema para el builder BuilderJS portado.
 *
 * El motor BuilderJS usa `themeMediaUrl` como base para resolver las imágenes
 * relativas de las plantillas EJS. Aquí lo resolvemos a una ruta del módulo que
 * sirve los ficheros de resources/themes/{theme}/.
 */
class UrlService
{
    /**
     * URL base de assets del tema. Si $path es null, devuelve el PREFIJO.
     */
    public function generateThemeAssetUrl(string $theme, ?string $path = null): string
    {
        return route('manager.page_templates.theme_asset', [
            'theme' => $theme,
            'path' => $path,
        ], false);
    }
}
