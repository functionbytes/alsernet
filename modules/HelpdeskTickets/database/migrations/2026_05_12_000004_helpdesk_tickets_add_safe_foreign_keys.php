<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds foreign-key constraints that are safe to create (same-connection tables only).
 *
 * Cross-connection notes (why some FKs are NOT created here):
 *  - helpdesk_tickets.assignee_id → users.id
 *      SKIPPED: `users` lives on the default mysql connection; InnoDB cannot
 *      enforce FK constraints across database schemas in MariaDB.
 *      The assignee_id column already has an index (added in migration _000001).
 *
 *  - helpdesk_ticket_categories.ai_suggested_category_id
 *      NOT a real column — ai_suggested_category_id lives on helpdesk_tickets
 *      (added by 2026_04_20_000180). FK skipped (cross-connection for users).
 *
 * FK created here:
 *  - helpdesk_ticket_categories.default_sla_policy_id
 *      → helpdesk_ticket_sla_policies.id  (same helpdesk connection, onDelete set null)
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            ! $schema->hasTable('helpdesk_ticket_categories') ||
            ! $schema->hasColumn('helpdesk_ticket_categories', 'default_sla_policy_id') ||
            ! $schema->hasTable('helpdesk_ticket_sla_policies')
        ) {
            return;
        }

        if ($this->fkExists('helpdesk_ticket_categories', 'default_sla_policy_id')) {
            return;
        }

        try {
            $schema->table('helpdesk_ticket_categories', function (Blueprint $table) {
                $table->foreign('default_sla_policy_id', 'fk_htc_default_sla_policy')
                    ->references('id')
                    ->on('helpdesk_ticket_sla_policies')
                    ->onDelete('set null');
            });
        } catch (Throwable $e) {
            // FK may fail if orphaned rows exist — log and continue; the rest
            // of the migration is already applied.
            logger()->warning('Could not add FK fk_htc_default_sla_policy', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_categories')) {
            return;
        }

        if (! $this->fkExists('helpdesk_ticket_categories', 'default_sla_policy_id')) {
            return;
        }

        try {
            $schema->table('helpdesk_ticket_categories', function (Blueprint $table) {
                $table->dropForeign('fk_htc_default_sla_policy');
            });
        } catch (Throwable) {
            // Already removed — safe to ignore
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Check whether a FK constraint already exists on the given column. */
    private function fkExists(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return collect(DB::connection($this->connection)->select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        ))->isNotEmpty();
    }
};
