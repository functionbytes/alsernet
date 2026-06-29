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
        Schema::create('page_preview_tokens', function (Blueprint $table) {
            $table->id();

            // Page relationship
            $table->foreignId('page_id')
                ->constrained('pages')
                ->onDelete('cascade');

            // Token information
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');

            // Creator and tracking
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('viewed_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['page_id', 'token']);
            $table->index('expires_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_preview_tokens');
    }
};
