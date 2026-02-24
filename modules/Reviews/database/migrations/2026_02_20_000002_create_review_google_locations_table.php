<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_google_locations', function (Blueprint $table) {
            $table->id();

            // Connection relationship
            $table->foreignId('connection_id')
                ->constrained('review_google_connections')
                ->cascadeOnDelete()
                ->comment('Google connection this location belongs to');

            // Google identifiers
            $table->string('google_location_id', 255)->unique()->comment('Google Business Profile location ID');
            $table->string('google_account_id', 100)->comment('Google account ID for this location');

            // Location details
            $table->string('name', 255)->comment('Business name');
            $table->string('address', 500)->nullable()->comment('Full address');
            $table->string('phone', 50)->nullable()->comment('Contact phone');
            $table->string('website_url', 500)->nullable()->comment('Website URL');

            // Review stats
            $table->decimal('average_rating', 3, 2)->nullable()->comment('Average star rating (0.00 - 5.00)');
            $table->unsignedInteger('total_reviews')->default(0)->comment('Total review count');

            // Status
            $table->boolean('is_verified')->default(false)->comment('Location is verified on Google');
            $table->boolean('is_active')->default(true)->comment('Location is active for sync');

            // Additional metadata
            $table->json('metadata_json')->nullable()->comment('Additional Google metadata');

            // Sync tracking
            $table->timestamp('synced_at')->nullable()->comment('Last sync timestamp');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('connection_id');
            $table->index('google_location_id');
            $table->index('google_account_id');
            $table->index('is_active');
            $table->index('synced_at');
            $table->index(['connection_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_google_locations');
    }
};
