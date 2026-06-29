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
        Schema::create('social_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();

            // Interaction type
            $table->enum('type', ['comment', 'mention', 'message', 'reply', 'share'])->default('comment');

            // External IDs from social networks
            $table->string('external_id')->nullable()->index();
            $table->string('external_parent_id')->nullable(); // For replies/threads

            // Author information
            $table->string('author_id')->nullable(); // External user ID
            $table->string('author_username')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_avatar')->nullable();

            // Content
            $table->text('content');
            $table->string('media_url')->nullable();
            $table->string('external_url')->nullable(); // Link to original comment/mention

            // Status
            $table->boolean('is_read')->default(false)->index();
            $table->boolean('is_archived')->default(false)->index();
            $table->boolean('is_replied')->default(false);
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();

            // Reply tracking
            $table->text('reply_content')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Store additional network-specific data

            $table->timestamps();

            // Indexes for performance
            $table->index(['account_id', 'is_read', 'created_at']);
            $table->index(['post_id', 'type']);
            $table->index(['social_account_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_mentions');
    }
};
