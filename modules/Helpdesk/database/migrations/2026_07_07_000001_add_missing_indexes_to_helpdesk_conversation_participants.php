<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A diferencia de sus tablas hermanas (conversation_items, conversation_reads,
 * conversation_tag_pivot, conversation_views), helpdesk_conversation_participants
 * nunca tuvo FK hacia helpdesk_conversations. Antes de crearla se purgan las filas
 * huérfanas (conversation_id que ya no existe en helpdesk_conversations); si no,
 * la constraint fallaría al añadirse.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_conversation_participants') || ! $schema->hasColumn('helpdesk_conversation_participants', 'conversation_id')) {
            return;
        }

        DB::connection($this->connection)
            ->table('helpdesk_conversation_participants')
            ->whereNotIn('conversation_id', function ($query) {
                $query->select('id')->from('helpdesk_conversations');
            })
            ->delete();

        $schema->table('helpdesk_conversation_participants', function (Blueprint $table) {
            if (! $this->hasForeignKey('helpdesk_conversation_participants', 'conversation_id')) {
                $table->foreign('conversation_id')
                    ->references('id')->on('helpdesk_conversations')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_conversation_participants')) {
            return;
        }

        $schema->table('helpdesk_conversation_participants', function (Blueprint $table) {
            if ($this->hasForeignKey('helpdesk_conversation_participants', 'conversation_id')) {
                $table->dropForeign(['conversation_id']);
            }
        });
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        return collect(Schema::connection($this->connection)->getForeignKeys($table))
            ->pluck('columns')
            ->contains(fn (array $columns) => in_array($column, $columns, true));
    }
};
