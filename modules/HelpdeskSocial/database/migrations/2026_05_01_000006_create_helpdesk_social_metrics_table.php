<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_social_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('social_account_id')->nullable()->constrained('helpdesk_social_accounts')->nullOnDelete();
            $table->string('platform', 32);
            $table->unsignedInteger('comments_received')->default(0);
            $table->unsignedInteger('messages_received')->default(0);
            $table->unsignedInteger('replies_sent')->default(0);
            $table->unsignedInteger('auto_replies_sent')->default(0);
            $table->unsignedInteger('manual_replies_sent')->default(0);
            $table->unsignedInteger('escalated_count')->default(0);
            $table->unsignedInteger('spam_detected')->default(0);
            $table->unsignedInteger('avg_response_time_seconds')->nullable();
            $table->unsignedInteger('first_response_time_seconds')->nullable();
            $table->decimal('automation_rate', 5, 2)->default(0.0);
            $table->json('intents_breakdown')->nullable();
            $table->json('sentiment_breakdown')->nullable();
            $table->json('hourly_distribution')->nullable();
            $table->timestamps();

            $table->unique(['date', 'social_account_id']);
            $table->index(['date', 'platform']);
            $table->index('social_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_social_metrics');
    }
};
