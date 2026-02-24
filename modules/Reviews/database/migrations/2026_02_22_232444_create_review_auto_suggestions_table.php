<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_auto_suggestions', function (Blueprint $table) {
            $table->id();

            // Trigger keywords as JSON array
            $table->json('trigger_keywords')->comment('Keywords that trigger this suggestion');

            // Rating range (e.g., "1-2" for negative, "4-5" for positive, "3" for neutral)
            $table->string('rating_range', 10)->comment('Rating range (e.g., "1-2", "3", "4-5")');

            // Reference to reply template
            $table->foreignId('suggested_template_id')
                ->constrained('review_reply_templates')
                ->cascadeOnDelete()
                ->comment('Template to suggest for this match');

            // Priority for ordering suggestions
            $table->unsignedInteger('priority')->default(0)->comment('Priority for ordering (higher = shown first)');

            // Active status
            $table->boolean('is_active')->default(true)->comment('Whether this suggestion is active');

            $table->timestamps();

            // Indexes for performance
            $table->index('rating_range');
            $table->index('is_active');
            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_auto_suggestions');
    }
};
