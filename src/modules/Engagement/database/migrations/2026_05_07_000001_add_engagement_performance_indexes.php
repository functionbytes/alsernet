<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('engagement_events', 'idx_events_inbox_type_created', ['inbox_id', 'event_name', 'created_at']);
        $this->addIndexIfMissing('engagement_events', 'idx_events_session_created', ['session_token', 'created_at']);
        $this->addIndexIfMissing('engagement_events', 'idx_events_occurred', ['occurred_at']);

        $this->addIndexIfMissing('engagement_visitor_sessions', 'idx_sessions_token', ['session_token']);
        // Column is_active does not exist on engagement_visitor_sessions
        // $this->addIndexIfMissing('engagement_visitor_sessions', 'idx_sessions_active', ['updated_at', 'is_active']);
        $this->addIndexIfMissing('engagement_visitor_sessions', 'idx_sessions_customer', ['customer_id']);

        $this->addIndexIfMissing('engagement_visitor_scores', 'idx_scores_customer_inbox', ['customer_id', 'inbox_id']);
        $this->addIndexIfMissing('engagement_visitor_scores', 'idx_scores_session', ['session_token']);
        $this->addIndexIfMissing('engagement_visitor_scores', 'idx_scores_segment', ['segment']);

        $this->addIndexIfMissing('engagement_trigger_rules', 'idx_triggers_inbox_active', ['inbox_id', 'is_active']);
        $this->addIndexIfMissing('engagement_personalization_rules', 'idx_personalizations_inbox_active', ['inbox_id', 'is_active']);
        $this->addIndexIfMissing('engagement_platform_integrations', 'idx_integrations_inbox_active', ['inbox_id', 'is_active']);
    }

    public function down(): void
    {
        Schema::table('engagement_events', function (Blueprint $table) {
            $table->dropIndex(['idx_events_inbox_type_created']);
            $table->dropIndex(['idx_events_session_created']);
            $table->dropIndex(['idx_events_occurred']);
        });

        Schema::table('engagement_visitor_sessions', function (Blueprint $table) {
            $table->dropIndex(['idx_sessions_token']);
            // $table->dropIndex(['idx_sessions_active']);
            $table->dropIndex(['idx_sessions_customer']);
        });

        Schema::table('engagement_visitor_scores', function (Blueprint $table) {
            $table->dropIndex(['idx_scores_customer_inbox']);
            $table->dropIndex(['idx_scores_session']);
            $table->dropIndex(['idx_scores_segment']);
        });

        Schema::table('engagement_trigger_rules', function (Blueprint $table) {
            $table->dropIndex(['idx_triggers_inbox_active']);
        });

        Schema::table('engagement_personalization_rules', function (Blueprint $table) {
            $table->dropIndex(['idx_personalizations_inbox_active']);
        });

        Schema::table('engagement_platform_integrations', function (Blueprint $table) {
            $table->dropIndex(['idx_integrations_inbox_active']);
        });
    }

    /**
     * @param  array<string>  $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $exists = DB::selectOne(
            'SELECT 1 as found FROM information_schema.STATISTICS 
             WHERE table_schema = DATABASE() 
             AND table_name = ? 
             AND index_name = ?',
            [$table, $indexName]
        );

        if ($exists?->found) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName, $columns) {
            $table->index($columns, $indexName);
        });
    }
};
