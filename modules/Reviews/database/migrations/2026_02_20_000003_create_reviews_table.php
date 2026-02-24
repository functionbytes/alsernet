<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Location relationship
            $table->foreignId('location_id')
                ->constrained('review_google_locations')
                ->cascadeOnDelete()
                ->comment('Google location this review belongs to');

            // Google identifiers
            $table->string('google_review_id', 255)->unique()->comment('Unique Google review identifier');

            // Reviewer information
            $table->string('reviewer_name', 255)->comment('Reviewer display name');
            $table->string('reviewer_photo_url', 1000)->nullable()->comment('Reviewer profile photo URL');

            // Review content
            $table->enum('star_rating', ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'])->comment('Star rating from Google');
            $table->text('comment')->nullable()->comment('Review text comment');

            // Review timestamps (from Google)
            $table->timestamp('review_time')->comment('When review was posted on Google');
            $table->timestamp('update_time')->nullable()->comment('When review was last updated on Google');

            // Google reply (if any)
            $table->text('google_reply_text')->nullable()->comment('Business reply from Google');
            $table->timestamp('google_reply_time')->nullable()->comment('When reply was posted on Google');

            // Raw data
            $table->json('raw_json')->nullable()->comment('Full raw Google API response');

            // Sync tracking
            $table->timestamp('synced_at')->nullable()->comment('Last sync timestamp');
            $table->timestamps();

            // Indexes for performance
            $table->index('location_id');
            $table->index('google_review_id');
            $table->index('star_rating');
            $table->index('review_time');
            $table->index(['location_id', 'star_rating']);
            $table->index(['location_id', 'review_time']);
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
