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
        Schema::create('social_short_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->text('original_url');
            $table->string('short_code', 10)->unique();
            $table->string('custom_alias')->nullable()->unique();
            $table->integer('clicks')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->json('utm_parameters')->nullable(); // UTM tracking
            $table->timestamps();

            $table->index('account_id');
            $table->index('short_code');
            $table->index('custom_alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_short_urls');
    }
};
