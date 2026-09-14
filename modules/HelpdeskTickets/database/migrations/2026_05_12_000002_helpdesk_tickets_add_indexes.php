<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds performance indexes on frequently queried columns.
 *
 * All index additions are guarded so the migration is idempotent.
 *
 * Tables:
 *  - helpdesk_tickets:       category_id, priority, sla_resolution_due_at,
 *                            last_activity_at, group_id (simple)
 *                            + composite SLA check index (sla_resolution_breached,
 *                              sla_resolution_due_at, closed_at)
 *                            + composite escalation index (priority, closed_at,
 *                              escalated_at)
 *  - helpdesk_ticket_items:   user_id
 *  - helpdesk_ticket_comments: user_id
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $this->addIndexesOnTickets();
        $this->addIndexOnItems();
        $this->addIndexOnComments();
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'tickets_category_id_index');
                $this->dropIndexIfExists($table, 'tickets_priority_index');
                $this->dropIndexIfExists($table, 'tickets_sla_resolution_due_at_index');
                $this->dropIndexIfExists($table, 'tickets_last_activity_at_index');
                $this->dropIndexIfExists($table, 'tickets_group_id_index');
                $this->dropIndexIfExists($table, 'tickets_sla_check_idx');
                $this->dropIndexIfExists($table, 'tickets_escalation_idx');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_items')) {
            Schema::connection($this->connection)->table('helpdesk_ticket_items', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'ticket_items_user_id_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_comments')) {
            Schema::connection($this->connection)->table('helpdesk_ticket_comments', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'ticket_comments_user_id_index');
            });
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function addIndexesOnTickets(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_tickets')) {
            return;
        }

        $schema->table('helpdesk_tickets', function (Blueprint $table) use ($schema) {
            // Simple indexes (skip if column missing or index already present)
            $simpleIndexes = [
                'category_id' => 'tickets_category_id_index',
                'priority' => 'tickets_priority_index',
                'sla_resolution_due_at' => 'tickets_sla_resolution_due_at_index',
                'last_activity_at' => 'tickets_last_activity_at_index',
                'group_id' => 'tickets_group_id_index',
            ];

            foreach ($simpleIndexes as $column => $indexName) {
                if ($schema->hasColumn('helpdesk_tickets', $column) && ! $this->indexExists('helpdesk_tickets', $indexName)) {
                    $table->index($column, $indexName);
                }
            }

            // Composite index for CheckSlaBreaches / SendSlaWarnings jobs
            if (
                $schema->hasColumn('helpdesk_tickets', 'sla_resolution_breached') &&
                $schema->hasColumn('helpdesk_tickets', 'sla_resolution_due_at') &&
                $schema->hasColumn('helpdesk_tickets', 'closed_at') &&
                ! $this->indexExists('helpdesk_tickets', 'tickets_sla_check_idx')
            ) {
                $table->index(
                    ['sla_resolution_breached', 'sla_resolution_due_at', 'closed_at'],
                    'tickets_sla_check_idx'
                );
            }

            // Composite index for EscalateTicketsJob
            if (
                $schema->hasColumn('helpdesk_tickets', 'priority') &&
                $schema->hasColumn('helpdesk_tickets', 'closed_at') &&
                $schema->hasColumn('helpdesk_tickets', 'escalated_at') &&
                ! $this->indexExists('helpdesk_tickets', 'tickets_escalation_idx')
            ) {
                $table->index(
                    ['priority', 'closed_at', 'escalated_at'],
                    'tickets_escalation_idx'
                );
            }
        });
    }

    private function addIndexOnItems(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_items')) {
            return;
        }

        if (
            $schema->hasColumn('helpdesk_ticket_items', 'user_id') &&
            ! $this->indexExists('helpdesk_ticket_items', 'ticket_items_user_id_index')
        ) {
            $schema->table('helpdesk_ticket_items', function (Blueprint $table) {
                $table->index('user_id', 'ticket_items_user_id_index');
            });
        }
    }

    private function addIndexOnComments(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_comments')) {
            return;
        }

        if (
            $schema->hasColumn('helpdesk_ticket_comments', 'user_id') &&
            ! $this->indexExists('helpdesk_ticket_comments', 'ticket_comments_user_id_index')
        ) {
            $schema->table('helpdesk_ticket_comments', function (Blueprint $table) {
                $table->index('user_id', 'ticket_comments_user_id_index');
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

    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (Throwable) {
            // Index did not exist — safe to ignore
        }
    }
};
