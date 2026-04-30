<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OPTIMIZE FOR DASHBOARD QUERIES:
     * DashboardMetricsService has excessive nested whereHas() causing N+1 issues.
     * Solution: Add denormalized account_id to conversations for direct filtering.
     *
     * Current problem:
     *   Conversation::whereHas('inbox', fn($q) => $q->where('account_id', $x))
     *   → Requires JOIN every time
     *
     * Solution:
     *   Add account_id directly to chat_conversations (already exists!)
     *   Just need better composite indexes for common dashboard patterns.
     */
    public function up(): void
    {
        // The account_id already exists on chat_conversations via FK
        // We just need better composite indexes for dashboard metrics

        Schema::table('chat_conversations', function (Blueprint $table) {
            // Dashboard overview: count by account + status + date filters
            $table->index(['account_id', 'status_id', 'created_at'], 'hd_conv_dash_overview');

            // Dashboard resolved today: account + status + updated_at
            $table->index(['account_id', 'status_id', 'updated_at'], 'hd_conv_dash_resolved');

            // Dashboard unassigned: account + assignee_id + status_id
            $table->index(['account_id', 'assignee_id', 'status_id'], 'hd_conv_dash_unassigned');

            // Priority distribution: account + priority_id
            $table->index(['account_id', 'priority_id'], 'hd_conv_account_priority');

            // Inbox distribution: already covered by existing index
        });

        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            // Dashboard message count today: account + created_at
            $table->index(['account_id', 'created_at'], 'hd_messages_dash_today');

            // First response time calculation: account + message_type + created_at
            $table->index(['account_id', 'message_type', 'created_at'], 'hd_messages_response_time');
        });

        // Optimize User queries for agent metrics
        // (Assuming User model has account_id and availability_status - verify in app/Models/User.php)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'availability_status')) {
            Schema::table('users', function (Blueprint $table) {
                // Active agents query: account_id + availability_status
                if (! $this->hasIndex('users', 'availability_status')) {
                    $table->index(['availability_status'], 'users_availability_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'availability_status')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->hasIndex('users', 'users_availability_status')) {
                    $table->dropIndex('users_availability_status');
                }
            });
        }

        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->dropIndex('hd_messages_response_time');
            $table->dropIndex('hd_messages_dash_today');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('hd_conv_account_priority');
            $table->dropIndex('hd_conv_dash_unassigned');
            $table->dropIndex('hd_conv_dash_resolved');
            $table->dropIndex('hd_conv_dash_overview');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $column): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);

        foreach ($indexes as $index) {
            if (in_array($column, $index->getColumns())) {
                return true;
            }
        }

        return false;
    }
};
