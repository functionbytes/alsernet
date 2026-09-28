<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MarkOverdueTicketsCommand filtra por sla_first_response_due_at y
 * sla_next_response_due_at (cada uno junto a su flag *_breached y a la
 * columna que indica que la etapa ya se cerró: first_response_at para la
 * primera respuesta, closed_at para la siguiente). El único compuesto que
 * existe hoy en esta tabla para chequeos de SLA es tickets_sla_check_idx
 * (sla_resolution_breached, sla_resolution_due_at, closed_at), que cubre la
 * resolución pero no estas dos etapas: sin índice propio, cada ejecución del
 * comando (ticket:autooverdue) hace un full scan de helpdesk_tickets.
 *
 * Se sigue el mismo orden de columnas que tickets_sla_check_idx (flag,
 * fecha límite, columna de cierre de la etapa) para que el índice sirva
 * tanto al filtro por igualdad del flag como al rango sobre la fecha límite.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_tickets')) {
            return;
        }

        if (
            $schema->hasColumn('helpdesk_tickets', 'sla_first_response_breached') &&
            $schema->hasColumn('helpdesk_tickets', 'sla_first_response_due_at') &&
            $schema->hasColumn('helpdesk_tickets', 'first_response_at') &&
            ! $this->indexExists('helpdesk_tickets', 'tickets_first_response_check_idx')
        ) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->index(
                    ['sla_first_response_breached', 'sla_first_response_due_at', 'first_response_at'],
                    'tickets_first_response_check_idx'
                );
            });
        }

        if (
            $schema->hasColumn('helpdesk_tickets', 'sla_next_response_breached') &&
            $schema->hasColumn('helpdesk_tickets', 'sla_next_response_due_at') &&
            $schema->hasColumn('helpdesk_tickets', 'closed_at') &&
            ! $this->indexExists('helpdesk_tickets', 'tickets_next_response_check_idx')
        ) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->index(
                    ['sla_next_response_breached', 'sla_next_response_due_at', 'closed_at'],
                    'tickets_next_response_check_idx'
                );
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_tickets')) {
            return;
        }

        $schema->table('helpdesk_tickets', function (Blueprint $table) {
            foreach (['tickets_first_response_check_idx', 'tickets_next_response_check_idx'] as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (Throwable) {
                    // Index did not exist — safe to ignore
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();
    }
};
