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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('social_campaigns')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Post Type and Content
            $table->string('type')->default('text'); // text, link, media, video
            $table->text('content')->nullable();

            // Media
            $table->json('media')->nullable(); // Array of media files

            // Link Preview
            $table->string('link_url')->nullable();
            $table->json('link_preview')->nullable(); // Open Graph data

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Status
            $table->string('status')->default('draft'); // draft, scheduled, publishing, published, failed
            $table->text('error_message')->nullable();

            // Publishing Data
            $table->json('publish_results')->nullable(); // Results per network
            $table->integer('retry_count')->default(0);

            // Engagement (cached from post_stats)
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['account_id', 'status', 'scheduled_at']);
            $table->index('created_by');
            $table->index('campaign_id');
            $table->index('social_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
