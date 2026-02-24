<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();

            // Review relationship
            $table->foreignId('review_id')
                ->constrained('reviews')
                ->cascadeOnDelete()
                ->comment('Review being replied to');

            // Reply content
            $table->text('reply_text')->comment('Reply message text');

            // Reply workflow status
            $table->enum('status', ['draft', 'approved', 'published', 'failed'])
                ->default('draft')
                ->comment('Reply workflow status');

            // Error tracking
            $table->text('error_message')->nullable()->comment('Error message if publish failed');
            $table->unsignedInteger('error_count')->default(0)->comment('Number of failed publish attempts');

            // User tracking
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who created the reply');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who approved the reply');

            // Timestamps
            $table->timestamp('approved_at')->nullable()->comment('When reply was approved');
            $table->timestamp('published_at')->nullable()->comment('When reply was published to Google');
            $table->timestamps();

            // Indexes
            $table->index('review_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index(['review_id', 'status']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_replies');
    }
};
