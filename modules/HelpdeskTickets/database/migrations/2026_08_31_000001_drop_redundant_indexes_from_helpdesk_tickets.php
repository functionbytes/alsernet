<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * helpdesk_tickets acumulaba 31 índices para 28 combinaciones distintas de
 * columnas, fruto de migraciones sucesivas que crearon el mismo índice con dos
 * convenciones de nombre (helpdesk_tickets_* y tickets_*).
 *
 * Se retiran seis que no aportan nada y cuestan escritura en cada INSERT y en
 * cada UPDATE de una tabla que escribe mucho:
 *
 *  - Tres duplicados EXACTOS de otro índice ya existente (uno de ellos duplica
 *    incluso un UNIQUE, que ya es un índice).
 *  - Tres que son PREFIJO de un índice compuesto que ya los cubre: MySQL usa el
 *    compuesto para las consultas por la primera columna, así que el suelto es
 *    trabajo de mantenimiento sin ninguna lectura que lo aproveche.
 *
 * Nada de esto cambia qué consultas se pueden resolver por índice.
 *
 * Se retira además el índice FULLTEXT sobre `description`. No lo usa nadie:
 * la búsqueda de tickets es Ticket::scopeSearch(), que hace LIKE '%…%' sobre
 * ticket_number, subject y el nombre del cliente — no hay un solo
 * MATCH…AGAINST ni whereFullText() contra esta tabla en todo el módulo (sí lo
 * hay contra conversaciones, en ConversationFilter). Un FULLTEXT es de los
 * índices más caros de mantener en escritura, así que cobrarlo por cero
 * lecturas no tiene sentido. Si en algún momento se quiere búsqueda fulltext
 * de verdad, hay que rehacerla sobre (subject, description) y cambiar
 * scopeSearch() a whereFullText(); down() lo restaura tal cual estaba.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Índice a eliminar => índice que ya cubre su función.
     *
     * @var array<string, string>
     */
    private const REDUNDANT = [
        // Duplicados exactos
        'helpdesk_tickets_ticket_number_index' => 'helpdesk_tickets_ticket_number_unique (ticket_number)',
        'tickets_sla_resolution_due_at_index' => 'helpdesk_tickets_sla_resolution_due_at_index (sla_resolution_due_at)',
        'tickets_category_id_index' => 'helpdesk_tickets_category_id_index (category_id)',
        // Prefijos de un compuesto existente
        'helpdesk_tickets_customer_id_index' => 'helpdesk_tickets_customer_created_index (customer_id, created_at)',
        'helpdesk_tickets_assignee_id_index' => 'helpdesk_tickets_assignee_status_index (assignee_id, status_id)',
        'tickets_priority_index' => 'tickets_escalation_idx (priority, closed_at, escalated_at)',
        // FULLTEXT sin ninguna consulta que lo use — ver docblock.
        'helpdesk_tickets_search_fulltext' => 'ninguna consulta lo usa (scopeSearch va por LIKE)',
    ];

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            return;
        }

        foreach (array_keys(self::REDUNDANT) as $index) {
            if (! $this->indexExists($index)) {
                continue;
            }

            try {
                DB::connection($this->connection)->statement("ALTER TABLE `helpdesk_tickets` DROP INDEX `{$index}`");
            } catch (QueryException $e) {
                // El supuesto "duplicado" de este indice (ver REDUNDANT) puede
                // no existir en este entorno concreto -- el historial de
                // migraciones diverge entre copias. Si MySQL se niega porque el
                // indice sostiene una FK (error 1553) y no hay otro que la
                // cubra, no es seguro borrarlo aqui: se deja tal cual y sigue
                // con el resto en vez de tumbar todo el deploy por una
                // optimizacion de escritura que no aplica en esta copia.
                if (str_contains($e->getMessage(), '1553')) {
                    Log::warning("No se pudo eliminar el indice redundante {$index} de helpdesk_tickets: sostiene una FK y no hay otro indice equivalente en este entorno. Se deja como esta.");

                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Se recrean tal cual estaban, para que la migración sea reversible aunque
     * volver a tenerlos no aporte nada.
     */
    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            return;
        }

        $columns = [
            'helpdesk_tickets_ticket_number_index' => 'ticket_number',
            'tickets_sla_resolution_due_at_index' => 'sla_resolution_due_at',
            'tickets_category_id_index' => 'category_id',
            'helpdesk_tickets_customer_id_index' => 'customer_id',
            'helpdesk_tickets_assignee_id_index' => 'assignee_id',
            'tickets_priority_index' => 'priority',
        ];

        if (! $this->indexExists('helpdesk_tickets_search_fulltext')) {
            DB::connection($this->connection)->statement('ALTER TABLE `helpdesk_tickets` ADD FULLTEXT `helpdesk_tickets_search_fulltext` (`description`)');
        }

        foreach ($columns as $index => $column) {
            if (! $this->indexExists($index)) {
                DB::connection($this->connection)->statement("ALTER TABLE `helpdesk_tickets` ADD INDEX `{$index}` (`{$column}`)");
            }
        }
    }

    private function indexExists(string $index): bool
    {
        return DB::connection($this->connection)
            ->select('SHOW INDEX FROM `helpdesk_tickets` WHERE Key_name = ?', [$index]) !== [];
    }
};
