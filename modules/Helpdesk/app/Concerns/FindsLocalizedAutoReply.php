<?php

namespace Modules\Helpdesk\Concerns;

/**
 * Búsqueda del mejor mensaje configurado para un canal + idioma del cliente,
 * de más específico a más genérico: canal+idioma > canal+genérico >
 * global+idioma > global+genérico. Compartido por OffHoursResponse,
 * ConversationGreeting y ConversationFarewell — mismas columnas
 * (channel, language, is_active), mismo criterio de prioridad.
 *
 * Un resultado con `language` no nulo fue redactado a mano por un admin para
 * ese idioma exacto — el caller debe enviarlo tal cual, sin traducir. Un
 * resultado con `language` nulo es el genérico (se traduce al vuelo).
 */
trait FindsLocalizedAutoReply
{
    public static function findForChannel(?string $channel, ?string $language = null): ?static
    {
        $language = $language ? strtolower(substr($language, 0, 2)) : null;

        $candidates = array_unique(array_filter([
            $channel && $language ? ['channel' => $channel, 'language' => $language] : null,
            $channel ? ['channel' => $channel, 'language' => null] : null,
            $language ? ['channel' => null, 'language' => $language] : null,
            ['channel' => null, 'language' => null],
        ]), SORT_REGULAR);

        foreach ($candidates as $where) {
            $query = static::query()->where('is_active', true);
            $query = $where['channel'] === null ? $query->whereNull('channel') : $query->where('channel', $where['channel']);
            $query = $where['language'] === null ? $query->whereNull('language') : $query->where('language', $where['language']);

            $match = $query->first();

            if ($match) {
                return $match;
            }
        }

        return null;
    }
}
