<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Composite indexes for common query patterns
            // Note: account_id excluded (single-account app - no selectivity benefit)

            // Filter "my conversations" by status
            $table->index(['status_id', 'assignee_id'], 'idx_conversations_status_assignee');

            // Team inbox views sorted by activity
            $table->index(['team_id', 'last_activity_at'], 'idx_conversations_team_activity');

            // Inbox-specific views sorted by activity
            $table->index(['inbox_id', 'last_activity_at'], 'idx_conversations_inbox_activity');

            // Agent workload views (assigned conversations by activity)
            $table->index(['assignee_id', 'last_activity_at'], 'idx_conversations_assignee_activity');

            // Status-filtered lists sorted by activity
            $table->index(['status_id', 'last_activity_at'], 'idx_conversations_status_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_status_assignee');
            $table->dropIndex('idx_conversations_team_activity');
            $table->dropIndex('idx_conversations_inbox_activity');
            $table->dropIndex('idx_conversations_assignee_activity');
            $table->dropIndex('idx_conversations_status_activity');
        });
    }
};
