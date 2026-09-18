<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HelpdeskReportsController's "top agents" query filters by closed_at range
 * and groups by assignee_id. The existing composite indexes on this table
 * (tickets_sla_check_idx, tickets_escalation_idx) lead with a different
 * column, so a free closed_at range scan can't use them.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_tickets')) {
            return;
        }

        if (
            $schema->hasColumn('helpdesk_tickets', 'closed_at') &&
            $schema->hasColumn('helpdesk_tickets', 'assignee_id') &&
            ! $this->indexExists('helpdesk_tickets', 'tickets_top_agents_report_idx')
        ) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->index(['closed_at', 'assignee_id'], 'tickets_top_agents_report_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                try {
                    $table->dropIndex('tickets_top_agents_report_idx');
                } catch (Throwable) {
                    // Index did not exist — safe to ignore
                }
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
};
