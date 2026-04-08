<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for filtered log queries (status + date range)
        Schema::table('mailer_endpoint_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_logs_status_created');
            $table->index('job_id', 'idx_logs_job_id');
        });

        // Index for endpoint queries by template + active status
        Schema::table('mailer_endpoints', function (Blueprint $table) {
            $table->index(['mailer_template_id', 'is_active'], 'idx_endpoints_template_active');
        });
    }

    public function down(): void
    {
        Schema::table('mailer_endpoint_logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_status_created');
            $table->dropIndex('idx_logs_job_id');
        });

        Schema::table('mailer_endpoints', function (Blueprint $table) {
            $table->dropIndex('idx_endpoints_template_active');
        });
    }
};
