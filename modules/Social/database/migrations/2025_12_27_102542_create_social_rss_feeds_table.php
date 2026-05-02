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
        Schema::create('social_rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('social_campaigns')->nullOnDelete();
            $table->string('name');
            $table->text('feed_url');
            $table->boolean('auto_publish')->default(false);
            $table->text('post_template')->nullable(); // Template with {title}, {description}, {link}
            $table->json('hashtags')->nullable();
            $table->integer('fetch_interval')->default(60); // Minutes
            $table->timestamp('last_fetch_at')->nullable();
            $table->timestamp('next_fetch_at')->nullable();
            $table->integer('posts_created')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('account_id');
            $table->index('next_fetch_at');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_rss_feeds');
    }
};
