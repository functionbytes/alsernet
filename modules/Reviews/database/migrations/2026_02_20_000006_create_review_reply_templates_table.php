<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reply_templates', function (Blueprint $table) {
            $table->id();

            // Template identification
            $table->string('name', 255)->comment('Template name/title');

            // Template content with variables
            $table->text('body')->comment('Template text with {variable} placeholders');

            // Categorization
            $table->enum('category', ['positive', 'negative', 'neutral', 'general'])
                ->nullable()
                ->comment('Template category for quick filtering');

            // Status and usage
            $table->boolean('is_active')->default(true)->comment('Template is available for use');
            $table->unsignedInteger('usage_count')->default(0)->comment('Times this template was used');

            // Creator tracking
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who created the template');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index(['category', 'is_active']);
            $table->index('usage_count');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reply_templates');
    }
};
