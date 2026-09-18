<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relaciones hacia App\Models\User desde los modelos del helpdesk.
 *
 * Los modelos de este módulo viven en la conexión "helpdesk" y User en la
 * conexión por defecto de la aplicación, así que Eloquent no puede resolver la
 * relación por sí solo: hay que construir el BelongsTo a mano sobre una query
 * de User apuntada a su propia conexión.
 *
 * Antes esto estaba copiado diez veces (siete modelos) y con la conexión
 * escrita a mano como 'mysql'. En Docker 'mysql' y 'mariadb' apuntan al mismo
 * host y a la misma base — solo cambia el driver — así que en producción
 * funcionaba, a costa de abrir un segundo PDO innecesario contra el mismo
 * servidor. El daño real estaba en los tests: lo que pasaba por esa conexión
 * quedaba FUERA del rollback de DatabaseTransactions salvo que el test listara
 * 'mysql' en connectionsToTransact, y casi ninguno lo hacía. Llegó a borrar
 * datos reales del entorno compartido.
 *
 * Resolviendo la conexión desde config('database.default') se acabó: es la
 * misma que usa User, la misma que transaccionan los tests, y un único PDO.
 */
trait BelongsToHelpdeskUser
{
    /**
     * Query de User apuntada a la conexión donde User vive de verdad.
     */
    protected function helpdeskUserQuery(): Builder
    {
        $user = new User;
        $user->setConnection($this->helpdeskUserConnection());

        return $user->newQuery();
    }

    /**
     * BelongsTo entre este modelo (conexión "helpdesk") y User.
     *
     * @param  string  $foreignKey  Columna de ESTE modelo que apunta a users.id
     * @param  string  $relation  Nombre de la relación (el del método que llama)
     */
    protected function belongsToHelpdeskUser(string $foreignKey, string $relation): BelongsTo
    {
        return $this->newBelongsTo(
            $this->helpdeskUserQuery(),
            $this,
            $foreignKey,
            'id',
            $relation,
        );
    }

    /**
     * Conexión real de App\Models\User. El modelo no fija $connection, así que
     * usa la conexión por defecto de la aplicación; si algún día la fijara,
     * este método la respeta.
     */
    protected function helpdeskUserConnection(): string
    {
        return (new User)->getConnectionName() ?? config('database.default');
    }
}
