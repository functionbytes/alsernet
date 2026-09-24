<?php

namespace Modules\HelpdeskTickets\Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Los tests de reparto automático corren contra la BD real, donde hay
 * decenas de usuarios con el rol helpdesk-agent (fixtures que se colaron).
 * AssignmentService::getAvailableAgents() los encontraba y el ticket acababa
 * asignado a uno real (p. ej. id 20) en vez de al agente del test.
 *
 * Marca como no disponibles a todos los agentes que ya existían, SOLO si la
 * conexión de usuarios está dentro de la transacción del test (se revierte
 * al terminar). Sin transacción no toca nada: nunca debe apagar agentes de
 * verdad.
 */
trait IsolatesAgentPool
{
    /** Ver SharesHelpdeskPdo::beginDatabaseTransaction(). */
    protected function sharesUsersPdo(): bool
    {
        return true;
    }

    protected function isolateAgentPool(): void
    {
        $connection = DB::connection((new User)->getConnectionName());

        // PDO y no transactionLevel(): con el PDO compartido la transacción
        // la abre 'mariadb' y el contador de esta conexión sigue en 0.
        if (! $connection->getPdo()->inTransaction()) {
            $this->markTestSkipped('Sin transacción en la conexión de usuarios: no se aíslan los agentes reales.');
        }

        User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
            ->update(['available' => false]);
    }
}
