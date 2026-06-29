<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_moderations', function (Blueprint $table) {
            $table->id();

            // Review relationship (one-to-one)
            $table->foreignId('review_id')
                ->unique()
                ->constrained('reviews')
                ->cascadeOnDelete()
                ->comment('Review being moderated');

            // Moderation status
            $table->boolean('is_visible')->default(true)->comment('Show on website/widgets');
            $table->boolean('is_featured')->default(false)->comment('Highlight as featured review');

            // Organization
            $table->json('tags')->nullable()->comment('Custom tags for filtering');
            $table->text('internal_notes')->nullable()->comment('Private internal notes');

            // Moderation tracking
            $table->foreignId('moderated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who moderated this review');
            $table->timestamp('moderated_at')->nullable()->comment('When moderation was applied');

            $table->timestamps();

            // Indexes
            $table->index('review_id');
            $table->index('is_visible');
            $table->index('is_featured');
            $table->index('moderated_by');
            $table->index(['is_visible', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_moderations');
    }
};
