<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_sync_failures', function (Blueprint $table) {
            // entity_id is queried in retry helpers to locate the original record
            $table->index('entity_id');

            // erp_id is queried in retryProductSync() and search filters
            $table->index('erp_id');

            // Composite index for the "retryable" subquery: retry_count < max_retries
            $table->index(['retry_count', 'max_retries']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_sync_failures', function (Blueprint $table) {
            $table->dropIndex(['entity_id']);
            $table->dropIndex(['erp_id']);
            $table->dropIndex(['retry_count', 'max_retries']);
        });
    }
};
