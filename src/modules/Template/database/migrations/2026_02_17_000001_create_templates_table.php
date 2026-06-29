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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();

            // Template configuration
            $table->string('template_path')->nullable()->comment('Ruta en filesystem: public/templates/{slug}');
            $table->string('inherit')->nullable()->comment('Slug del template padre (herencia)');

            // Status and ownership
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Author metadata
            $table->string('author')->nullable();
            $table->string('version')->default('1.0.0');

            // Soft deletes and timestamps
            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index('slug');
            $table->index('user_id');
            $table->index('inherit');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
