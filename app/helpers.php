<?php

/**
 * Apply filters to a value (compatibility function for WordPress-style hooks).
 * For now, this simply returns the value as no hooks are registered.
 * This can be extended in the future to support a proper hook system.
 *
 * @param  string  $hook  The filter name
 * @param  mixed  $value  The value to filter
 * @param  mixed  ...$args  Additional arguments passed to callbacks
 * @return mixed
 */
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, ...$args): mixed
    {
        return $value;
    }
}

if (! function_exists('clean')) {
    /**
     * Sanitiza HTML con HTMLPurifier para prevenir XSS en contenido
     * proveniente de fuentes no confiables (mensajes de clientes, canned replies, etc.).
     * Permite tags comunes de WYSIWYG/email y bloquea script, iframe, javascript:, onclick...
     */
    function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        static $purifier = null;

        if ($purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,em,u,s,a[href|title|target],ul,ol,li,blockquote,code,pre,h1,h2,h3,h4,h5,h6,hr,img[src|alt|title|width|height],table,thead,tbody,tr,th,td,span,div');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('AutoFormat.RemoveEmpty', false);
            $config->set('Cache.DefinitionImpl', null);
            $purifier = new HTMLPurifier($config);
        }

        return $purifier->purify($html);
    }
}
