<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_widget_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('review_google_locations')
                ->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->json('allowed_origins')->nullable();
            $table->unsignedTinyInteger('max_reviews')->default(5);
            $table->unsignedTinyInteger('min_rating')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_widget_tokens');
    }
};
