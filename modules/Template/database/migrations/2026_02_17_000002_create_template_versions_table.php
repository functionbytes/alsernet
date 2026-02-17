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
        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();

            // Foreign key to templates table
            $table->foreignId('template_id')
                ->constrained('templates')
                ->cascadeOnDelete();

            // Version tracking
            $table->unsignedInteger('version');

            // Content snapshot
            $table->longText('content')->nullable();
            $table->text('description')->nullable();

            // Changed fields JSON (qué cambió)
            $table->json('changed_fields')->nullable();

            // User who created this version
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Metadata snapshot
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('author')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index(['template_id', 'version']);
            $table->index('template_id');
            $table->index('created_at');
            $table->unique(['template_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_versions');
    }
};
