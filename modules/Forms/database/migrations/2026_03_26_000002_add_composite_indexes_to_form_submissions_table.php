<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite indexes on form_submissions for dashboard date-range and unread queries
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->index(
                ['form_id', 'status', 'created_at'],
                'form_submissions_form_status_created_index'
            );

            $table->index(
                ['form_id', 'is_read', 'created_at'],
                'form_submissions_form_is_read_created_index'
            );
        });

        // Composite index on form_abandon_tracking for session lookup
        Schema::table('form_abandon_tracking', function (Blueprint $table) {
            $table->index(
                ['form_id', 'session_token'],
                'form_abandon_tracking_form_session_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex('form_submissions_form_status_created_index');
            $table->dropIndex('form_submissions_form_is_read_created_index');
        });

        Schema::table('form_abandon_tracking', function (Blueprint $table) {
            $table->dropIndex('form_abandon_tracking_form_session_index');
        });
    }
};
