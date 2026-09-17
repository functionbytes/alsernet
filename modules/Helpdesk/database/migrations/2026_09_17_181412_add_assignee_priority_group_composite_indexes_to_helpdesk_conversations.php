<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ConversationView permite combinar libremente varios filtros a la vez
 * (helpdesk_conversation_views.filters), pero solo las vistas guardadas
 * REALES ancladas en `assignee_id` cruzan un segundo filtro directo sobre
 * `helpdesk_conversations` (is_open se resuelve aparte, vía EXISTS contra
 * helpdesk_conversation_statuses, así que no compite por este índice):
 *
 *   - "Mi cola urgente"  -> assignee_id = ? AND priority = ?
 *   - "Mis devoluciones" -> assignee_id = ? AND group_id = ?
 *
 * Con volumen de producción simulado (~6.000 conversaciones, agente con
 * ~1.625 asignadas) EXPLAIN mostraba que el optimizador ignoraba el índice
 * existente (assignee_id, deleted_at, last_message_at) y prefería un table
 * scan completo (type=ALL, rows=5907, Using filesort) porque ~27% de la
 * tabla ya cumplía solo el primer filtro. Añadiendo el segundo filtro a la
 * cabeza del índice el plan pasó a type=ref (rows=70 / rows=460) SIN
 * filesort -y el propio ORDER BY last_message_at DESC queda resuelto por el
 * orden del índice, al ser la última columna-, con una consulta hasta 4x más
 * rápida medida en caliente.
 *
 * No se añade un índice por cada combinación teórica (channel+priority,
 * group+priority, etc.): ninguna otra aparece en las vistas sembradas o
 * guardadas de verdad, así que sería especulativo penalizar cada
 * insert/update de la tabla (ya tiene ~28 índices) sin evidencia de uso.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            // Nombre completo (helpdesk_conversations_assignee_priority_deleted_last_message_index)
            // supera los 64 caracteres que admite MySQL/MariaDB como identificador.
            if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conv_assignee_priority_deleted_lastmsg_index')) {
                $table->index(
                    ['assignee_id', 'priority', 'deleted_at', 'last_message_at'],
                    'helpdesk_conv_assignee_priority_deleted_lastmsg_index'
                );
            }

            if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conv_assignee_group_deleted_lastmsg_index')) {
                $table->index(
                    ['assignee_id', 'group_id', 'deleted_at', 'last_message_at'],
                    'helpdesk_conv_assignee_group_deleted_lastmsg_index'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            // dropIndexIfExists() no existe en Blueprint (ni hay macro que lo
            // registre) — usar el hasIndex() ya definido en este archivo.
            if ($this->hasIndex('helpdesk_conversations', 'helpdesk_conv_assignee_priority_deleted_lastmsg_index')) {
                $table->dropIndex('helpdesk_conv_assignee_priority_deleted_lastmsg_index');
            }

            if ($this->hasIndex('helpdesk_conversations', 'helpdesk_conv_assignee_group_deleted_lastmsg_index')) {
                $table->dropIndex('helpdesk_conv_assignee_group_deleted_lastmsg_index');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection($this->connection)->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
