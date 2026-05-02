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
        Schema::create('social_listening_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name'); // Keyword/hashtag/mention text
            $table->enum('type', ['keyword', 'hashtag', 'mention', 'competitor'])->default('keyword');
            $table->json('networks'); // ['facebook', 'instagram', 'twitter', 'linkedin']
            $table->boolean('is_active')->default(true);
            $table->enum('sentiment_filter', ['all', 'positive', 'negative', 'neutral'])->default('all');
            $table->boolean('notify_on_match')->default(false);
            $table->timestamp('last_scanned_at')->nullable();
            $table->integer('mentions_count')->default(0);
            $table->string('color')->default('#3498db'); // For UI tagging
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for performance
            $table->index(['account_id', 'is_active']);
            $table->index(['account_id', 'type']);
            $table->index('last_scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_listening_keywords');
    }
};
