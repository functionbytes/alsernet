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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('inbox_id')->nullable()->constrained('chat_inboxes')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('chat_customers')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('chat_customer_sessions')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('chat_teams')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('chat_groups')->nullOnDelete();
            $table->foreignId('sla_id')->nullable()->constrained('chat_sla_policies')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('chat_conversation_priorities')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('chat_conversation_statuses')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('language', 10)->nullable();
            $table->string('subject')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->json('custom_attributes')->nullable();
            $table->string('cached_label_list')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('first_response_sla_breached')->default(false);
            $table->boolean('resolution_sla_breached')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['account_id', 'status_id']);
            $table->index(['customer_id', 'status_id']);
            $table->index('inbox_id');
            $table->index('language');
            $table->index('session_id');
            $table->index('team_id');
            $table->index('group_id');
            $table->index('sla_id');
            $table->index('assignee_id');
            $table->index('status_id');
            $table->index('is_archived');
            $table->index('assigned_at');
            $table->index('priority_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
