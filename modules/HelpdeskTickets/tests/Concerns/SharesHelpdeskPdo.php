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
     *
     * 'mysql' se agrega aparte (PDO propio, no compartido con 'mariadb'): es
     * la conexión real de Modules\Core\Models\Setting/tabla `settings`
     * (confirmado en runtime, no un alias de 'mariadb'). Sin ella, cualquier
     * test que toque Setting::set()/setEncrypted() o DB::table('settings')
     * escribe DE VERDAD sin rollback — pasó en la práctica: un test de
     * canales de correo (incoming_email) borró un canal real ya configurado
     * en el entorno compartido. `settings` no tiene FKs hacia tablas de
     * helpdesk, así que darle su propia transacción aparte no reproduce el
     * problema de lock cruzado que este trait resuelve para 'helpdesk'.
     */
    protected array $connectionsToTransact = ['mariadb', 'mysql'];

    protected function beginDatabaseTransaction()
    {
        $shared = DB::connection('mariadb');

        DB::connection('helpdesk')->setPdo($shared->getPdo());
        DB::connection('helpdesk')->setReadPdo($shared->getPdo());

        $this->baseBeginDatabaseTransaction();
    }
}
