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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('template', 60)->nullable()->default('default');
            $table->text('description')->nullable();

            // Status and ownership
            $table->enum('status', ['draft', 'published', 'pending'])
                ->default('draft');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // SEO fields
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();

            // Publishing
            $table->timestamp('published_at')->nullable();

            // Soft deletes and timestamps
            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index('slug');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
