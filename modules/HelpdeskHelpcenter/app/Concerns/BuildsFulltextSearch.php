<?php

namespace Modules\HelpdeskHelpcenter\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Shared helper to turn a plain search string into a FULLTEXT boolean-mode term.
 *
 * Used by widget, public and manager searches so they all benefit from the
 * `helpdesk_helpcenter_articles_fulltext` index on (title, body) instead of
 * non-sargable `LIKE '%term%'` full table scans.
 */
trait BuildsFulltextSearch
{
    /**
     * Builds a FULLTEXT boolean mode term from a plain search string.
     * Returns null when the term is too short for FULLTEXT (< 3 chars or all tokens < 3 chars).
     */
    protected function buildBooleanTerm(string $term): ?string
    {
        $tokens = array_filter(
            explode(' ', $term),
            fn (string $t) => strlen($t) >= 3
        );

        if (empty($tokens)) {
            return null;
        }

        return implode(' ', array_map(fn (string $t) => '+'.$t.'*', $tokens));
    }

    /**
     * Si el fallback `orWhere(... LIKE '%term%')` debe combinarse con el
     * MATCH...AGAINST fulltext, o si el MATCH debe ir solo.
     *
     * El LIKE se añadió porque InnoDB FULLTEXT no ve filas todavía no
     * comprometidas — así que la condición real a comprobar es "¿hay una
     * transacción abierta en la conexión 'helpdesk'?" (como la que envuelve
     * cada test con DatabaseTransactions), NO "¿estamos en entorno de
     * test?": `app()->environment('testing')` parecía la señal obvia, pero
     * en este proyecto `APP_ENV` llega fijado a nivel de proceso Docker
     * (docker/laravel.env, APP_ENV=local) y gana sobre el `<env>` de
     * phpunit.xml (que no fuerza el valor) — con esa comprobación el
     * entorno de test se detectaba como "local" y el LIKE dejaba de
     * incluirse, rompiendo las búsquedas contra filas recién creadas y aún
     * sin comprometer. transactionLevel() no depende de esa configuración
     * rota y refleja la condición real.
     *
     * Al ir por `OR` en el mismo query, este LIKE con comodín inicial
     * anulaba el índice FULLTEXT en CADA búsqueda de producción (MySQL/
     * MariaDB no puede resolver una rama de un OR con índice si otra rama
     * del mismo OR fuerza un escaneo completo) — fuera de una transacción
     * abierta el MATCH ya resuelve por sí solo.
     */
    protected function shouldFallbackToLikeSearch(): bool
    {
        return DB::connection('helpdesk')->transactionLevel() > 0;
    }
}
