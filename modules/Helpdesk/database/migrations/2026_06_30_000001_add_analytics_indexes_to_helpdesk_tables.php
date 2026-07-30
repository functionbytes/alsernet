<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * IDX-01: analytics/reporting indexes for closed_at and CSAT agent_id.
     *
     * Idempotent: every index is guarded by hasTable/hasColumn/hasIndex so the
     * migration is safe even though the conversations indexes were already added
     * by 2026_04_23_000001 (they will be skipped here).
     */
    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if ($this->hasColumn('helpdesk_conversations', 'closed_at')
                    && ! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_closed_at_index')) {
                    $table->index('closed_at', 'helpdesk_conversations_closed_at_index');
                }

                if ($this->hasColumn('helpdesk_conversations', 'closed_at')
                    && $this->hasColumn('helpdesk_conversations', 'assignee_id')
                    && ! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_assignee_closed_index')) {
                    $table->index(['assignee_id', 'closed_at'], 'helpdesk_conversations_assignee_closed_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_csat_ratings')) {
            Schema::connection($this->connection)->table('helpdesk_csat_ratings', function (Blueprint $table) {
                if ($this->hasColumn('helpdesk_csat_ratings', 'agent_id')
                    && ! $this->hasIndex('helpdesk_csat_ratings', 'helpdesk_csat_ratings_agent_id_index')) {
                    $table->index('agent_id', 'helpdesk_csat_ratings_agent_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        // Solo se revierte el índice de csat_ratings: los índices de conversations
        // pertenecen a 2026_04_23_000001 (up() los salta si ya existen), así que no
        // deben eliminarse aquí. dropIndex con guard hasIndex (dropIndexIfExists no existe en Laravel).
        if (Schema::connection($this->connection)->hasTable('helpdesk_csat_ratings')) {
            Schema::connection($this->connection)->table('helpdesk_csat_ratings', function (Blueprint $table) {
                if ($this->hasIndex('helpdesk_csat_ratings', 'helpdesk_csat_ratings_agent_id_index')) {
                    $table->dropIndex('helpdesk_csat_ratings_agent_id_index');
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
