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
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();

            // Foreign key to pages table
            $table->foreignId('page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            // Version tracking
            $table->unsignedInteger('version_number');

            // Content snapshot
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('description')->nullable();

            // User who created this version
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Additional metadata (optional)
            $table->string('template', 60)->nullable();
            $table->enum('status', ['draft', 'published', 'pending'])->nullable();
            $table->string('slug')->nullable();

            // SEO fields snapshot
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index(['page_id', 'version_number']);
            $table->index('page_id');
            $table->index('created_at');
            $table->unique(['page_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_versions');
    }
};
