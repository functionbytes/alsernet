<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops 2 of the 8 secondary indexes on `engagement_events` confirmed
 * redundant by a full code audit (2026-07-07): `idx_events_inbox_type_created`
 * (inbox_id, event_name, created_at) and `idx_events_session_created`
 * (session_token, created_at) duplicate the prefix of two base indexes
 * already covering (inbox_id, event_name, occurred_at) and
 * (session_token, occurred_at) — no query in the module filters/sorts this
 * table by `created_at` (only `occurred_at`, used 26x). Removing them cuts
 * write overhead on the highest-volume table without losing read coverage.
 * `idx_events_occurred` (occurred_at alone) is kept: ArchiveOldEventsJob
 * filters by it with no other leading column.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('engagement_events', function (Blueprint $table) {
            if ($this->indexExists('idx_events_inbox_type_created')) {
                $table->dropIndex('idx_events_inbox_type_created');
            }

            if ($this->indexExists('idx_events_session_created')) {
                $table->dropIndex('idx_events_session_created');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('engagement_events', function (Blueprint $table) {
            if (! $this->indexExists('idx_events_inbox_type_created')) {
                $table->index(['inbox_id', 'event_name', 'created_at'], 'idx_events_inbox_type_created');
            }

            if (! $this->indexExists('idx_events_session_created')) {
                $table->index(['session_token', 'created_at'], 'idx_events_session_created');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        $result = Schema::connection($this->connection)->getConnection()->select(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['engagement_events', $indexName]
        );

        return ((int) $result[0]->aggregate) > 0;
    }
};
