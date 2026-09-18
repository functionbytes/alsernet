<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * helpdesk_conversations.group_id y .brand_id existen desde hace tiempo (ver
 * add_group_id_to_helpdesk_conversations_table y create_helpdesk_brands_table)
 * pero nunca tuvieron FK hacia helpdesk_groups/helpdesk_brands pese a vivir en la
 * misma conexión 'helpdesk'. Se limpian huérfanos dejando la columna a NULL (nunca
 * se borra la conversación) y luego se añade la FK con nullOnDelete(): borrar un
 * grupo o una marca no debe arrastrar sus conversaciones.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_conversations')) {
            return;
        }

        if ($schema->hasColumn('helpdesk_conversations', 'group_id')) {
            $this->nullifyOrphans('group_id', 'helpdesk_groups');
        }

        if ($schema->hasColumn('helpdesk_conversations', 'brand_id')) {
            $this->nullifyOrphans('brand_id', 'helpdesk_brands');
        }

        $schema->table('helpdesk_conversations', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('helpdesk_conversations', 'group_id') && ! $this->hasForeignKey('helpdesk_conversations', 'group_id')) {
                $table->foreign('group_id')
                    ->references('id')->on('helpdesk_groups')
                    ->nullOnDelete();
            }

            if ($schema->hasColumn('helpdesk_conversations', 'brand_id') && ! $this->hasForeignKey('helpdesk_conversations', 'brand_id')) {
                $table->foreign('brand_id')
                    ->references('id')->on('helpdesk_brands')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_conversations')) {
            return;
        }

        $schema->table('helpdesk_conversations', function (Blueprint $table) {
            if ($this->hasForeignKey('helpdesk_conversations', 'group_id')) {
                $table->dropForeign(['group_id']);
            }

            if ($this->hasForeignKey('helpdesk_conversations', 'brand_id')) {
                $table->dropForeign(['brand_id']);
            }
        });
    }

    /**
     * Pone a NULL las referencias que apuntan a un id inexistente en $referencedTable.
     * Nunca borra la conversación: solo desvincula el group_id/brand_id huérfano.
     */
    private function nullifyOrphans(string $column, string $referencedTable): void
    {
        DB::connection($this->connection)
            ->table('helpdesk_conversations')
            ->whereNotNull($column)
            ->whereNotIn($column, function ($query) use ($referencedTable) {
                $query->select('id')->from($referencedTable);
            })
            ->update([$column => null]);
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        return collect(Schema::connection($this->connection)->getForeignKeys($table))
            ->pluck('columns')
            ->contains(fn (array $columns) => in_array($column, $columns, true));
    }
};
