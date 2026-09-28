<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos índices detectados perfilando /panel/helpdesk/tickets/{id}/data
 * (28-sep-2026):
 *
 * - helpdesk_ticket_mails: MailBuilder::build() (antes
 *   TicketDetailDataService) hace
 *   `ticket->mails()->reorder()->latest()->limit(50)`, es decir
 *   `WHERE ticket_id = ? ORDER BY created_at DESC LIMIT 50`. La tabla ya
 *   tiene helpdesk_ticket_mails_ticket_id_index (ticket_id) y
 *   helpdesk_ticket_mails_ticket_id_id_index (ticket_id, id) — ninguno de
 *   los dos sirve para el ORDER BY created_at: MySQL filtra por ticket_id
 *   con el índice y luego ordena en memoria/disco (filesort) las filas
 *   resultantes. Un ticket con historial de correo denso paga ese filesort
 *   en cada apertura del panel.
 *
 * - helpdesk_ticket_views: TicketsCrudController::index() (línea 64) filtra
 *   `WHERE user_id = ? OR is_shared = 1` en cada carga del listado de
 *   tickets para resolver las vistas guardadas visibles del agente. Hoy solo
 *   existe idx_6530 (ticket_id, user_id) — no cubre user_id en primera
 *   posición sin ticket_id ni is_shared en absoluto, así que esa consulta no
 *   puede apoyarse en ningún índice existente.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            $schema->hasTable('helpdesk_ticket_mails') &&
            $schema->hasColumn('helpdesk_ticket_mails', 'ticket_id') &&
            $schema->hasColumn('helpdesk_ticket_mails', 'created_at') &&
            ! $this->indexExists('helpdesk_ticket_mails', 'helpdesk_ticket_mails_ticket_id_created_at_index')
        ) {
            $schema->table('helpdesk_ticket_mails', function (Blueprint $table) {
                $table->index(['ticket_id', 'created_at'], 'helpdesk_ticket_mails_ticket_id_created_at_index');
            });
        }

        if (
            $schema->hasTable('helpdesk_ticket_views') &&
            $schema->hasColumn('helpdesk_ticket_views', 'user_id') &&
            $schema->hasColumn('helpdesk_ticket_views', 'is_shared') &&
            ! $this->indexExists('helpdesk_ticket_views', 'helpdesk_ticket_views_user_id_is_shared_index')
        ) {
            $schema->table('helpdesk_ticket_views', function (Blueprint $table) {
                $table->index(['user_id', 'is_shared'], 'helpdesk_ticket_views_user_id_is_shared_index');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_mails')) {
            $schema->table('helpdesk_ticket_mails', function (Blueprint $table) {
                try {
                    $table->dropIndex('helpdesk_ticket_mails_ticket_id_created_at_index');
                } catch (Throwable) {
                    // Index did not exist — safe to ignore
                }
            });
        }

        if ($schema->hasTable('helpdesk_ticket_views')) {
            $schema->table('helpdesk_ticket_views', function (Blueprint $table) {
                try {
                    $table->dropIndex('helpdesk_ticket_views_user_id_is_shared_index');
                } catch (Throwable) {
                    // Index did not exist — safe to ignore
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();
    }
};
