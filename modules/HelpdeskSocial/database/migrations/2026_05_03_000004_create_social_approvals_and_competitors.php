<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Approval workflows for sensitive actions
        Schema::create('helpdesk_social_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_comment_id')->constrained('helpdesk_social_comments')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 32); // reply, escalate, hide, delete
            $table->json('payload')->nullable(); // proposed reply body, etc.
            $table->string('status', 16)->default('pending'); // pending, approved, rejected
            $table->text('approver_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'approver_user_id']);
            $table->index('social_comment_id');
        });

        // Competitor benchmarking
        Schema::create('helpdesk_social_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->nullable()->constrained('helpdesk_social_accounts')->nullOnDelete(); // reference account
            $table->string('name');
            $table->string('platform', 32);
            $table->string('external_id')->nullable();
            $table->string('username')->nullable();
            $table->string('profile_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform', 'external_id']);
        });

        Schema::create('helpdesk_social_competitor_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_competitor_id')->constrained('helpdesk_social_competitors')->cascadeOnDelete();
            $table->string('metric_type', 32); // followers, engagement_rate, posts_count, avg_likes, avg_comments
            $table->decimal('value', 15, 4)->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['social_competitor_id', 'metric_type', 'captured_at'], 'social_comp_metrics_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_competitor_metrics');
        Schema::dropIfExists('helpdesk_social_competitors');
        Schema::dropIfExists('helpdesk_social_approval_requests');
    }
};
