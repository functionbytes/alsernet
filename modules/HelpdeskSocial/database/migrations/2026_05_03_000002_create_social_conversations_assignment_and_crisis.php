<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Local conversation threading (distinct from helpdesk_conversation_id)
        Schema::create('helpdesk_social_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('helpdesk_social_accounts')->cascadeOnDelete();
            $table->string('platform', 32);
            $table->string('external_conversation_id')->nullable(); // platform thread id
            $table->string('external_post_id')->nullable();
            $table->string('participant_external_id'); // customer id
            $table->string('participant_name')->nullable();
            $table->string('status', 16)->default('open'); // open, pending, resolved, spam
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['social_account_id', 'status'], 'social_conv_account_status_idx');
            $table->index(['platform', 'external_conversation_id'], 'social_conv_platform_ext_idx');
            $table->index('participant_external_id', 'social_conv_participant_idx');
        });

        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->foreignId('social_conversation_id')->nullable()->after('social_account_id')->constrained('helpdesk_social_conversations')->nullOnDelete();
        });

        // Assignment rules for smart routing
        Schema::create('helpdesk_social_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('conditions'); // platform, intent, urgency, keywords, sentiment
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_strategy', 32)->default('direct'); // direct, round_robin, least_busy
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agent workload tracking
        Schema::create('helpdesk_social_agent_workloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('active_assigned_count')->default(0);
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamps();
        });

        // Crisis mode
        Schema::create('helpdesk_social_crisis_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('helpdesk_social_accounts')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->boolean('crisis_mode_active')->default(false)->after('auto_reply_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->dropColumn('crisis_mode_active');
        });

        Schema::dropIfExists('helpdesk_social_crisis_modes');
        Schema::dropIfExists('helpdesk_social_agent_workloads');
        Schema::dropIfExists('helpdesk_social_assignment_rules');

        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->dropForeign(['social_conversation_id']);
            $table->dropColumn('social_conversation_id');
        });

        Schema::dropIfExists('helpdesk_social_conversations');
    }
};
