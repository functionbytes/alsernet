<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('review_google_locations')
                ->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('google_maps_url', 500);
            $table->string('place_id', 200)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index('location_id');
        });

        Schema::create('review_competitor_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')
                ->constrained('review_competitors')
                ->cascadeOnDelete();
            $table->decimal('avg_rating', 3, 2);
            $table->unsignedInteger('review_count');
            $table->timestamp('captured_at');

            $table->index(['competitor_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_competitor_snapshots');
        Schema::dropIfExists('review_competitors');
    }
};
