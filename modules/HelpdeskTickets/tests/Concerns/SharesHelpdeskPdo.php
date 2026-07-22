<?php

namespace Modules\HelpdeskTickets\Tests\Concerns;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Las conexiones lógicas "mariadb" y "helpdesk" apuntan a la misma BD de tests.
 * Si cada una abre su propia transacción (DatabaseTransactions con ambas), los
 * checks de FK entre tablas de una y otra (p. ej. helpdesk_ticket_assignments →
 * users) se bloquean entre sí: la fila padre está sin commitear en la OTRA
 * conexión y el S-lock del FK espera 50s hasta "Lock wait timeout".
 *
 * Solución: compartir el PDO de "mariadb" con "helpdesk" y transaccionar solo
 * "mariadb". Todo corre en UNA transacción (los FK ven las filas sin commitear)
 * y el rollback final revierte ambas "conexiones".
 */
trait SharesHelpdeskPdo
{
    use DatabaseTransactions {
        beginDatabaseTransaction as baseBeginDatabaseTransaction;
    }

    /**
     * Solo la conexión dueña del PDO compartido abre transacción: abrir otra en
     * "helpdesk" (mismo PDO) haría un commit implícito de la primera.
     */
    protected array $connectionsToTransact = ['mariadb'];

    protected function beginDatabaseTransaction()
    {
        $shared = DB::connection('mariadb');

        DB::connection('helpdesk')->setPdo($shared->getPdo());
        DB::connection('helpdesk')->setReadPdo($shared->getPdo());

        $this->baseBeginDatabaseTransaction();
    }
}
