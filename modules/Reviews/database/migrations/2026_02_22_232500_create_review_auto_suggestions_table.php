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

            // Trigger conditions
            $table->json('trigger_keywords')->comment('Array of keywords to match in review comments');
            $table->string('rating_range', 10)->comment('Rating range like "1-2", "3", "4-5"');

            // Template suggestion
            $table->foreignId('suggested_template_id')
                ->nullable()
                ->constrained('review_reply_templates')
                ->cascadeOnDelete()
                ->comment('Template to suggest when conditions match');

            // Configuration
            $table->integer('priority')->default(0)->comment('Higher priority suggestions shown first');
            $table->boolean('is_active')->default(true)->comment('Enable/disable this suggestion rule');

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('priority');
            $table->index(['is_active', 'priority']);
            $table->index('suggested_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_auto_suggestions');
    }
};
