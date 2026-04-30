<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Single column indexes for sorting
            $table->index('last_activity_at');
            $table->index('last_message_at');
            $table->index('snoozed_until');

            // Composite indexes for common query patterns
            $table->index(['account_id', 'status_id', 'last_activity_at'], 'hd_conv_account_status_activity');
            $table->index(['account_id', 'assignee_id'], 'hd_conv_account_assignee');
            $table->index(['account_id', 'inbox_id'], 'hd_conv_account_inbox');
            $table->index(['account_id', 'team_id'], 'hd_conv_account_team');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['last_message_at']);
            $table->dropIndex(['snoozed_until']);
            $table->dropIndex('hd_conv_account_status_activity');
            $table->dropIndex('hd_conv_account_assignee');
            $table->dropIndex('hd_conv_account_inbox');
            $table->dropIndex('hd_conv_account_team');
        });
    }
};
