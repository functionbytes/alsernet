<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasTicketNumber
{
    /**
     * Generate unique ticket number (TCK-YYYY-#####)
     */
    public static function generateTicketNumber(): string
    {
        $year = now()->year;
        $prefix = "TCK-{$year}-";

        // El lockForUpdate() de abajo SOLO surte efecto dentro de una
        // transacción: en autocommit, MySQL adquiere y suelta el lock en el
        // acto y dos creaciones simultáneas leen el mismo último número.
        // Siete de los once caminos de creación del módulo envolvían la
        // llamada en DB::transaction(); cuatro no (alta desde el panel de
        // agentes, ProcessRecurringTicketsJob, y las dos vías de
        // HelpdeskTicketBridgeService), y ahí el bloqueo era decorativo:
        // colisión contra el índice UNIQUE de ticket_number.
        //
        // Abrir aquí la transacción cubre los once de una vez y hace que
        // cualquier punto de creación futuro herede la protección sin tener
        // que acordarse.
        $connection = DB::connection(static::make()->getConnectionName());

        $generate = function () use ($prefix) {
            // withTrashed() es obligatorio: el índice UNIQUE de ticket_number
            // es a nivel de BD y no distingue soft-deleted — un ticket
            // fusionado/archivado-y-eliminado (Ticket::merge()/destroy())
            // sigue ocupando su número. Sin esto, generateTicketNumber() podía
            // "retroceder" tras el primer soft-delete y chocar con
            // UniqueConstraintViolationException (bug real encontrado probando
            // la ingesta de emails).
            //
            // El orden va por la parte NUMÉRICA, no por la cadena: ordenar el
            // string dejaba 'TCK-2026-100000' por debajo de 'TCK-2026-99999'
            // en cuanto se pasara de cinco cifras, y el contador retrocedía.
            $lastTicket = static::withTrashed()
                ->where('ticket_number', 'like', "{$prefix}%")
                ->orderByRaw('CAST(SUBSTRING(ticket_number, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
                ->lockForUpdate()
                ->first();

            if ($lastTicket) {
                // Extract the numeric part and increment
                $lastNumber = (int) substr($lastTicket->ticket_number, strlen($prefix));
                $newNumber = $lastNumber + 1;
            } else {
                // First ticket of the year
                $newNumber = 1;
            }

            return $prefix.str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        };

        // Si el PDO ya está dentro de una transacción, el SELECT … FOR UPDATE
        // de arriba ya es efectivo y no hay que abrir otra. Se mira el PDO y no
        // transactionLevel() porque varias conexiones lógicas pueden compartir
        // el mismo PDO: los tests del módulo lo hacen a propósito
        // (Tests\Concerns\SharesHelpdeskPdo apunta "helpdesk" al PDO de
        // "mariadb" para que los FK entre ambas no se bloqueen entre sí), y ahí
        // Laravel cree que "helpdesk" está a nivel 0 mientras el PDO ya tiene
        // una transacción abierta — pedirle otra revienta con
        // "There is already an active transaction".
        return $connection->getPdo()->inTransaction()
            ? $generate()
            : $connection->transaction($generate);
    }
}
