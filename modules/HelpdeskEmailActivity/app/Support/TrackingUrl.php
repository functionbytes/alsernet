<?php

namespace Modules\HelpdeskEmailActivity\Support;

use Illuminate\Support\Facades\URL;
use Modules\Core\Models\Setting;

/**
 * URL base con la que se construyen el píxel de apertura y los enlaces de clic.
 *
 * Importa más de lo que parece: esas URLs viajan DENTRO del correo y las abre el
 * destinatario desde su propio ordenador, no el panel. Si apuntan a un host que
 * solo existe en el servidor —`http://localhost:8092`, una IP privada, un
 * dominio `.test`— el píxel no carga y, peor, **los enlaces reescritos del
 * correo llevan a un sitio inexistente**: el cliente hace clic y no llega a
 * ninguna parte. Romper los enlaces de un correo ya enviado es mucho peor que
 * no medir sus aperturas.
 *
 * Por eso el seguimiento se puede apuntar a un dominio propio
 * (`helpdeskemailactivity.tracking_base_url`) distinto del que sirve el panel, y
 * por eso existe isPubliclyReachable(): la vista avisa cuando la base actual no
 * es alcanzable desde fuera.
 */
class TrackingUrl
{
    /**
     * Base propia configurada para el seguimiento, o cadena vacía si no hay.
     */
    public static function configured(): string
    {
        $value = Setting::get(
            'helpdeskemailactivity.tracking_base_url',
            config('helpdeskemailactivity.tracking_base_url'),
        );

        return rtrim(is_string($value) ? trim($value) : '', '/');
    }

    /**
     * Base efectiva: la propia si se fijó una, si no la de la aplicación.
     */
    public static function base(): string
    {
        $configured = self::configured();

        return $configured !== '' ? $configured : rtrim((string) config('app.url'), '/');
    }

    /**
     * Genera una ruta de seguimiento sobre la base efectiva.
     *
     * Con base propia se pide la ruta RELATIVA y se antepone la base, en vez de
     * sustituir un prefijo dentro de la URL absoluta: el generador de Laravel
     * fija su raíz al arrancar y no siempre coincide con `app.url`, así que un
     * str_replace sobre esa raíz fallaba en silencio y devolvía la URL interna.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters): string
    {
        $base = self::configured();

        if ($base === '') {
            return URL::route($name, $parameters);
        }

        return $base.URL::route($name, $parameters, false);
    }

    /**
     * ¿Puede el ordenador de un destinatario cualquiera abrir esta URL?
     *
     * No comprueba que el servidor responda —eso exigiría una petición saliente
     * en cada envío—, solo descarta lo que con certeza NO es alcanzable desde
     * fuera: localhost, direcciones privadas o reservadas y los dominios de
     * desarrollo terminados en .test/.local/.localhost/.invalid.
     */
    public static function isPubliclyReachable(?string $url = null): bool
    {
        $host = parse_url($url ?? self::base(), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower(trim($host, '[]'));

        foreach (['.test', '.local', '.localhost', '.invalid', '.example'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        // Un host con IP literal se evalúa directamente; uno con nombre se
        // considera público sin resolverlo (resolver DNS en cada envío sería
        // caro y frágil, y el aviso es informativo, no un bloqueo).
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
