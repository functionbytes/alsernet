<?php

namespace Modules\Optimize\Http\Middleware;

/**
 * Añade decoding="async" a las imágenes que no tengan el atributo.
 *
 * Permite que el navegador decodifique imágenes fuera del hilo principal,
 * reduciendo el bloqueo del main thread y mejorando LCP / TTI.
 *
 * No modifica imágenes que ya tengan decoding="sync" (casos excepcionales
 * donde el desarrollador quiere decodificación síncrona).
 */
class AddImageDecoding extends PageSpeed
{
    public function apply(string $buffer): string
    {
        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $matches): string {
                $attrs = $matches[1];

                // Ya tiene decoding → no tocar
                if (preg_match('/\bdecoding\s*=/i', $attrs)) {
                    return $matches[0];
                }

                // Insertar decoding="async" justo después de <img
                return '<img decoding="async"'.$attrs.'>';
            },
            $buffer
        ) ?? $buffer;
    }
}
