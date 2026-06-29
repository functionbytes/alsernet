<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // review_replies: add composite index (status, review_id) for status-first queries
        Schema::table('review_replies', function (Blueprint $table) {
            $table->index(['status', 'review_id']);
        });

        // reviews: add composite index (created_at, location_id) for date-filtered location queries
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['created_at', 'location_id']);
        });

        // review_moderations: add index on created_at for date-based sorting/filtering
        Schema::table('review_moderations', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropIndex(['status', 'review_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'location_id']);
        });

        Schema::table('review_moderations', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
