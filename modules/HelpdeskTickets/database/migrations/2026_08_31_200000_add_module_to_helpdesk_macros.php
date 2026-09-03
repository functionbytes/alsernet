<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `helpdesk_macros` la comparten dos modelos distintos —
 * Modules\HelpdeskTickets\Models\Macro (macros de ticket) y
 * Modules\Helpdesk\Models\Macro (macros de conversacion)— sin ningun campo
 * que diga de quien es cada fila. El resultado: cada panel de ajustes lista
 * las macros del otro, los contadores suman las dos, el binding de ruta deja
 * editar y borrar las ajenas, y al aplicarlas el ejecutor no reconoce el tipo
 * de accion y no hace nada en silencio (los vocabularios son disjuntos:
 * reply/set_status/... frente a send_reply/change_status/...).
 *
 * Esta columna es el discriminador de origen. Cada modelo anade un global
 * scope sobre ella, con lo que todas las consultas quedan acotadas sin tocar
 * los ~20 sitios que consultan la tabla.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    /** Acciones que solo existen en el vocabulario de conversaciones. */
    private const HELPDESK_ONLY = [
        'assign_agent', 'remove_tag', 'change_status', 'change_priority',
        'add_note', 'send_reply', 'resolve_conversation', 'close_conversation',
    ];

    /** Acciones que solo existen en el vocabulario de tickets. */
    private const TICKETS_ONLY = [
        'reply', 'internal_note', 'assign_user', 'set_priority', 'set_status', 'close',
    ];

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if (! $s->hasTable('helpdesk_macros')) {
            return;
        }

        if (! $s->hasColumn('helpdesk_macros', 'module')) {
            $s->table('helpdesk_macros', function (Blueprint $t) {
                $t->string('module', 32)->default('tickets')->after('id')->index();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        $s = Schema::connection($this->connection);

        if ($s->hasTable('helpdesk_macros') && $s->hasColumn('helpdesk_macros', 'module')) {
            $s->table('helpdesk_macros', function (Blueprint $t) {
                $t->dropIndex(['module']);
                $t->dropColumn('module');
            });
        }
    }

    /**
     * Reparte las filas existentes por el vocabulario de sus acciones. Si solo
     * usan acciones comunes (assign_group, add_tag) desempata la forma del
     * payload: Helpdesk anida los argumentos en `params`, Tickets los pone
     * sueltos en `value`/`body`.
     */
    private function backfill(): void
    {
        $rows = DB::connection($this->connection)
            ->table('helpdesk_macros')
            ->get(['id', 'actions']);

        foreach ($rows as $row) {
            $actions = json_decode((string) $row->actions, true);
            $actions = is_array($actions) ? $actions : [];

            $types = [];
            $hasParams = false;

            foreach ($actions as $action) {
                if (! is_array($action)) {
                    continue;
                }
                if (isset($action['type']) && is_string($action['type'])) {
                    $types[] = $action['type'];
                }
                if (array_key_exists('params', $action)) {
                    $hasParams = true;
                }
            }

            if (array_intersect($types, self::HELPDESK_ONLY) !== []) {
                $module = 'helpdesk';
            } elseif (array_intersect($types, self::TICKETS_ONLY) !== []) {
                $module = 'tickets';
            } else {
                $module = $hasParams ? 'helpdesk' : 'tickets';
            }

            DB::connection($this->connection)
                ->table('helpdesk_macros')
                ->where('id', $row->id)
                ->update(['module' => $module]);
        }
    }
};
