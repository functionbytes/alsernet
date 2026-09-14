<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for health dashboard queries that filter by status AND date range
        Schema::table('supplier_automation_executions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_executions_status_created_at');
        });

        // Index for history/report queries filtering by completion date
        Schema::table('supplier_sync_batches', function (Blueprint $table) {
            $table->index('completed_at', 'idx_sync_batches_completed_at');
        });

        // Composite index for queries filtering by source and ordering by creation time
        Schema::table('supplier_extraction_batches', function (Blueprint $table) {
            $table->index(['source_id', 'created_at'], 'idx_extraction_batches_source_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_automation_executions', function (Blueprint $table) {
            $table->dropIndex('idx_executions_status_created_at');
        });

        Schema::table('supplier_sync_batches', function (Blueprint $table) {
            $table->dropIndex('idx_sync_batches_completed_at');
        });

        Schema::table('supplier_extraction_batches', function (Blueprint $table) {
            $table->dropIndex('idx_extraction_batches_source_created_at');
        });
    }
};
