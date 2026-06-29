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
        Schema::create('social_post_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();

            // Engagement Metrics
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('views')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);

            // Calculated Metrics
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->decimal('ctr', 8, 2)->default(0); // Click-through rate

            // Raw Data from APIs
            $table->json('raw_data')->nullable();

            // Sync Info
            $table->timestamp('fetched_at');

            $table->timestamps();

            // Indexes
            $table->index('post_id');
            $table->index('fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_post_stats');
    }
};
