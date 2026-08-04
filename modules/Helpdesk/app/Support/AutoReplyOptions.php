<?php

namespace Modules\Helpdesk\Support;

/**
 * Opciones de canal/idioma compartidas por las 3 páginas de mensajes
 * automáticos (fuera de horario, bienvenida, despedida) — mismas columnas
 * (channel, language) en los 3 modelos.
 */
class AutoReplyOptions
{
    /**
     * @return array<string|null, string>
     */
    public static function channels(): array
    {
        return [
            null => 'Todos los canales',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Messenger',
            'instagram' => 'Instagram',
            'web' => 'Web (widget)',
        ];
    }

    /**
     * @return array<string|null, string>
     */
    public static function languages(): array
    {
        return [
            null => 'Automático (traducir)',
            'es' => 'Español',
            'en' => 'Inglés',
            'fr' => 'Francés',
            'de' => 'Alemán',
            'it' => 'Italiano',
            'pt' => 'Portugués',
        ];
    }

    /**
     * Slugs válidos de canal (sin la opción "todos"), para Rule::in() —
     * se derivan de channels() para que la validación nunca se desincronice
     * de las opciones que muestra el <select>.
     *
     * @return array<int, string>
     */
    public static function channelSlugs(): array
    {
        return array_values(array_filter(array_keys(self::channels())));
    }

    /**
     * Slugs válidos de idioma (sin la opción "automático"), para Rule::in().
     *
     * @return array<int, string>
     */
    public static function languageSlugs(): array
    {
        return array_values(array_filter(array_keys(self::languages())));
    }
}
