<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_auto_reply_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')
                ->constrained('review_google_locations')
                ->cascadeOnDelete()
                ->comment('Location this rule applies to');

            $table->string('name', 150)->comment('Human-readable rule name');

            $table->boolean('is_active')->default(true)->comment('Whether the rule is enabled');

            $table->json('conditions')
                ->comment('Matching conditions: min_rating, max_rating, has_comment');

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('review_reply_templates')
                ->nullOnDelete()
                ->comment('Reply template to use (overrides custom_text)');

            $table->text('custom_text')
                ->nullable()
                ->comment('Reply text when no template is set');

            $table->unsignedInteger('delay_minutes')
                ->default(0)
                ->comment('Minutes to wait before dispatching the reply');

            $table->timestamps();

            $table->index('location_id');
            $table->index('is_active');
            $table->index(['location_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_auto_reply_rules');
    }
};
