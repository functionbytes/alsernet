<?php

namespace Modules\Forms\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanea el HTML que un formulario puede pintar tal cual: el texto de un
 * consentimiento y el contenido de un bloque HTML.
 *
 * En el proyecto de origen esto era un helper global `clean()` declarado en
 * `app/helpers.php`. Aquí no existe, y la vista lo llamaba igualmente: el tipo
 * de campo `consent` reventaba con "Call to undefined function clean()" en
 * cuanto un formulario tenía uno. No se vio antes porque hasta ahora ningún
 * formulario del constructor usaba ese tipo.
 *
 * El allowlist permite `target` en los enlaces a propósito: los consentimientos
 * de la tienda enlazan a la política de privacidad con target="_blank", y
 * quitarlo sacaría al cliente del formulario a medio rellenar.
 */
class HtmlSanitizer
{
    private const ALLOWED = 'p,br,strong,b,em,i,u,s,ul,ol,li,'
        .'h2,h3,h4,h5,h6,blockquote,pre,code,hr,'
        .'a[href|title|target|rel],img[src|alt|title],span,div,small';

    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::purifier()->purify(self::neutraliseDangerousUris($html));
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', self::ALLOWED);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('AutoFormat.RemoveEmpty', false);
            // Sin caché en disco: el contenido es corto y así no hace falta un
            // directorio escribible sólo para esto.
            $config->set('Cache.DefinitionImpl', null);
            $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }

    /**
     * Neutraliza esquemas peligrosos en href/src antes de purificar, para que un
     * enlace malicioso quede inerte en vez de desaparecer entero (y el texto del
     * consentimiento siga leyéndose igual).
     */
    private static function neutraliseDangerousUris(string $html): string
    {
        $scheme = '(?:javascript|vbscript|data)';

        return (string) preg_replace(
            [
                '/(href|src)\s*=\s*"\s*'.$scheme.':[^"]*"/i',
                "/(href|src)\s*=\s*'\s*".$scheme.":[^']*'/i",
                '/(href|src)\s*=\s*'.$scheme.':[^\s>]*/i',
            ],
            ['$1="#"', "\$1='#'", '$1="#"'],
            $html
        );
    }
}
