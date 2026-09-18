<?php

namespace Modules\HelpdeskLivechat\Tests\Concerns;

use Modules\Helpdesk\Models\ConversationStatus;

/**
 * Seed idempotente del estado "open" que casi todos los tests de Livechat
 * necesitan (el flujo del widget resuelve el estado abierto por is_open=true y
 * las factories de Conversation asignan estado abierto por defecto). Sustituye
 * al bloque ConversationStatus::firstOrCreate([...'open'...]) que estaba
 * copiado en cada setUp().
 *
 * NOTA — por qué los tests de Livechat NO usan SharesHelpdeskPdo:
 * WidgetConversationService::createConversation() abre una transacción
 * EXPLÍCITA sobre la conexión 'helpdesk' (DB::connection('helpdesk')
 * ->transaction(...)). Con el PDO compartido de SharesHelpdeskPdo esa
 * transacción "real" haría COMMIT implícito de la transacción del test y
 * filtraría datos permanentemente a la BD compartida (ver la advertencia del
 * propio trait). Por eso este módulo:
 *   - transacciona AMBAS conexiones:
 *     `protected array $connectionsToTransact = ['mariadb', 'helpdesk'];`
 *   - y en los asserts de BD indica la conexión que hizo la escritura:
 *     `$this->assertDatabaseHas('tabla', [...], 'helpdesk')` — la conexión por
 *     defecto (mariadb) no ve filas sin commitear de la otra transacción.
 */
trait SeedsOpenConversationStatus
{
    protected function seedOpenConversationStatus(): ConversationStatus
    {
        return ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }
}
