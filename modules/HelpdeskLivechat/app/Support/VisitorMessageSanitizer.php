<?php

namespace Modules\HelpdeskLivechat\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanea el texto que escribe un visitante del widget antes de guardarlo.
 *
 * El servicio llamaba al helper global `clean()` de mews/purifier, que este
 * proyecto no tiene instalado: cada mensaje del visitante respondía 500
 * ("Call to undefined function clean()"). Mismo caso que Forms\Support\HtmlSanitizer.
 *
 * Un mensaje de chat es texto plano: se quitan todas las etiquetas (y el
 * contenido de <script>/<style>) con HTMLPurifier. `<` y `>` quedan escapados;
 * `&`, comillas y espacios duros se devuelven a su carácter porque no pueden
 * formar una etiqueta y, escapados, el visitante vería "&amp;" en su burbuja.
 */
class VisitorMessageSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $purified = self::purifier()->purify($text);

        return strtr($purified, [
            '&amp;' => '&',
            '&quot;' => '"',
            '&#039;' => "'",
            '&nbsp;' => ' ',
        ]);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', '');
            $config->set('Core.Encoding', 'UTF-8');
            // Sin caché en disco: mensajes cortos, no hace falta un directorio escribible.
            $config->set('Cache.DefinitionImpl', null);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
