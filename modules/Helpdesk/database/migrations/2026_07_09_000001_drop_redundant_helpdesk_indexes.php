<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina índices simples REDUNDANTES: cada uno es el prefijo izquierdo de un
 * índice compuesto ya existente (o duplicado de un unique), así que MySQL usa el
 * compuesto igual de bien y estos solo pagan coste de ESCRITURA en las tablas de
 * mayor tráfico (helpdesk_conversations, _items, _reads, tag_pivot, customers).
 *
 * Cada drop va guardado por comprobación de existencia (la BD puede tener drift),
 * de modo que up() es idempotente; down() recrea los índices simples.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private array $redundant = [
        'helpdesk_conversations' => [
            'helpdesk_conversations_assignee_id_index',
            'helpdesk_conversations_status_id_index',
            'helpdesk_conversations_group_id_index',
            'helpdesk_conversations_is_archived_index',
        ],
        'helpdesk_customers' => ['helpdesk_customers_email_index'],
        'helpdesk_conversation_items' => ['helpdesk_conversation_items_conversation_id_index'],
        'helpdesk_conversation_reads' => ['helpdesk_conversation_reads_conversation_id_index'],
        'helpdesk_conversation_tag_pivot' => ['helpdesk_conversation_tag_pivot_conversation_id_index'],
    ];

    /** @var array<string, array{0: string, 1: string}> índice => [tabla, columna] para down() */
    private array $columns = [
        'helpdesk_conversations_assignee_id_index' => ['helpdesk_conversations', 'assignee_id'],
        'helpdesk_conversations_status_id_index' => ['helpdesk_conversations', 'status_id'],
        'helpdesk_conversations_group_id_index' => ['helpdesk_conversations', 'group_id'],
        'helpdesk_conversations_is_archived_index' => ['helpdesk_conversations', 'is_archived'],
        'helpdesk_customers_email_index' => ['helpdesk_customers', 'email'],
        'helpdesk_conversation_items_conversation_id_index' => ['helpdesk_conversation_items', 'conversation_id'],
        'helpdesk_conversation_reads_conversation_id_index' => ['helpdesk_conversation_reads', 'conversation_id'],
        'helpdesk_conversation_tag_pivot_conversation_id_index' => ['helpdesk_conversation_tag_pivot', 'conversation_id'],
    ];

    public function up(): void
    {
        foreach ($this->redundant as $table => $indexes) {
            foreach ($indexes as $index) {
                if ($this->indexExists($table, $index)) {
                    Schema::connection('helpdesk')->table($table, fn (Blueprint $t) => $t->dropIndex($index));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $index => [$table, $column]) {
            if (! $this->indexExists($table, $index)) {
                Schema::connection('helpdesk')->table($table, fn (Blueprint $t) => $t->index($column, $index));
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::connection('helpdesk')->selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index],
        ) !== null;
    }
};
