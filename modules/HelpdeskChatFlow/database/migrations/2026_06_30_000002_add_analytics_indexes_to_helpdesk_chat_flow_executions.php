<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Indexes that back the analytics builders (drop-off groups by node_id, AI
     * metrics filter by node_type per session). The base table only ships
     * (session_id) and (session_id, status); these two add node-level lookups.
     *
     * Laravel has no Schema::hasIndex(), so each addition is guarded with
     * try/catch: re-running the migration (or a partially-applied one) is safe
     * and a pre-existing index never blocks the other.
     *
     * @var array<string, callable(Blueprint): void>
     */
    private function indexes(): array
    {
        return [
            'hcfe_node_id_index' => fn (Blueprint $table) => $table->index('node_id', 'hcfe_node_id_index'),
            'hcfe_session_node_type_index' => fn (Blueprint $table) => $table->index(['session_id', 'node_type'], 'hcfe_session_node_type_index'),
        ];
    }

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_chat_flow_executions')) {
            return;
        }

        foreach ($this->indexes() as $definition) {
            try {
                $schema->table('helpdesk_chat_flow_executions', $definition);
            } catch (Throwable $e) {
                // Index already exists — safe to ignore (idempotent re-run).
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_chat_flow_executions')) {
            return;
        }

        foreach (array_keys($this->indexes()) as $name) {
            try {
                $schema->table('helpdesk_chat_flow_executions', fn (Blueprint $table) => $table->dropIndex($name));
            } catch (Throwable $e) {
                // Index missing — safe to ignore.
            }
        }
    }
};
