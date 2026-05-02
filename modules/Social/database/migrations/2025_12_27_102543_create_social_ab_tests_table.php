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
        Schema::create('social_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('variant_a_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('variant_b_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->string('metric')->default('engagement'); // engagement, clicks, likes, comments
            $table->string('status')->default('running'); // running, completed, cancelled
            $table->integer('variant_a_score')->default(0);
            $table->integer('variant_b_score')->default(0);
            $table->foreignId('winner_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_ab_tests');
    }
};
