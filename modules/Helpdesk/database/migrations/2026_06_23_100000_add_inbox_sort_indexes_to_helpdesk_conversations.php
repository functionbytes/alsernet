<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * El listado del inbox ordena por `last_message_at DESC` y el modelo usa
     * SoftDeletes (filtro implícito `deleted_at IS NULL`). Los índices simples
     * existentes (`last_message_at`, `inbox_id+created_at`) no cubren ese sort
     * con el filtro de soft-delete, provocando filesort. Estos compuestos
     * permiten resolver WHERE deleted_at IS NULL [AND inbox_id = ?] ORDER BY
     * last_message_at vía index scan.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_deleted_last_message_index')) {
                $table->index(['deleted_at', 'last_message_at'], 'helpdesk_conversations_deleted_last_message_index');
            }

            if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_inbox_deleted_last_message_index')) {
                $table->index(['inbox_id', 'deleted_at', 'last_message_at'], 'helpdesk_conversations_inbox_deleted_last_message_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndexIfExists('helpdesk_conversations_deleted_last_message_index');
            $table->dropIndexIfExists('helpdesk_conversations_inbox_deleted_last_message_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection($this->connection)->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
