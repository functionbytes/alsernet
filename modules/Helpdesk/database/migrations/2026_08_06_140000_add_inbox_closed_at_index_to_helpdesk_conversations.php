<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AnalyticsAggregatorService::agentPerformance() filtra por
 * `c.closed_at BETWEEN ? AND ?` + `c.inbox_id IN (...)` (agentes restringidos
 * a bandeja, accessibleInboxIds()) — pero solo existían índices que cubren
 * cada columna por separado: (inbox_id, created_at) y (assignee_id,
 * closed_at), ninguno cubre ambos predicados de esta query a la vez. El
 * resultado ya está cacheado (remember(), TTL ~300s), así que el impacto es
 * bajo-medio (solo se nota cuando el caché expira/falla) — se añade aquí de
 * forma idempotente, igual que 2026_06_30_000001.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if ($this->hasColumn('helpdesk_conversations', 'closed_at')
                    && $this->hasColumn('helpdesk_conversations', 'inbox_id')
                    && ! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_inbox_closed_index')) {
                    $table->index(['inbox_id', 'closed_at'], 'helpdesk_conversations_inbox_closed_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if ($this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_inbox_closed_index')) {
                    $table->dropIndex('helpdesk_conversations_inbox_closed_index');
                }
            });
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::connection($this->connection)->hasColumn($table, $column);
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection($this->connection)->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
