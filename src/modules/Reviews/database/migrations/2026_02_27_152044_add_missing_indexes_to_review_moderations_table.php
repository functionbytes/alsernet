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
        Schema::table('review_moderations', function (Blueprint $table) {
            // Add index on moderated_at for date-based filtering and sorting
            $table->index('moderated_at');

            // Add composite index for common query pattern: filtering by review + visibility
            $table->index(['review_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_moderations', function (Blueprint $table) {
            $table->dropIndex(['moderated_at']);
            $table->dropIndex(['review_id', 'is_visible']);
        });
    }
};
