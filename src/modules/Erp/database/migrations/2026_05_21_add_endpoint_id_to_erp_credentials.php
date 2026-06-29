<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix dangling FK / missing indices flagged by the Erp module audit:
     *
     *   - ErpEndpoint::credentials() points at erp_credentials.endpoint_id but
     *     the column never existed → relation always returned empty.
     *
     *   - erp_endpoint_tokens allowed N tokens with the same name per endpoint
     *     (silent collisions when reading from logs by name).
     *
     *   - erp_endpoint_logs benefits from a (success, endpoint_id, created_at)
     *     composite for dashboard "top endpoints in last X days" queries.
     */
    public function up(): void
    {
        Schema::table('erp_credentials', function (Blueprint $table) {
            if (! Schema::hasColumn('erp_credentials', 'endpoint_id')) {
                $table->foreignId('endpoint_id')
                    ->nullable()
                    ->after('account_id')
                    ->constrained('erp_endpoints')
                    ->nullOnDelete();

                $table->index(['endpoint_id', 'is_active'], 'erp_credentials_endpoint_active_idx');
            }
        });

        Schema::table('erp_endpoint_tokens', function (Blueprint $table) {
            // SHOW INDEX equivalent: only add if not present
            $indexExists = collect(DB::select("SHOW INDEX FROM erp_endpoint_tokens WHERE Key_name = 'erp_endpoint_tokens_endpoint_name_unique'"))->isNotEmpty();
            if (! $indexExists) {
                $table->unique(['endpoint_id', 'name'], 'erp_endpoint_tokens_endpoint_name_unique');
            }
        });

        Schema::table('erp_endpoint_logs', function (Blueprint $table) {
            $indexExists = collect(DB::select("SHOW INDEX FROM erp_endpoint_logs WHERE Key_name = 'erp_endpoint_logs_success_endpoint_created_idx'"))->isNotEmpty();
            if (! $indexExists) {
                $table->index(['success', 'endpoint_id', 'created_at'], 'erp_endpoint_logs_success_endpoint_created_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erp_endpoint_logs', function (Blueprint $table) {
            $table->dropIndex('erp_endpoint_logs_success_endpoint_created_idx');
        });

        Schema::table('erp_endpoint_tokens', function (Blueprint $table) {
            $table->dropUnique('erp_endpoint_tokens_endpoint_name_unique');
        });

        Schema::table('erp_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('erp_credentials', 'endpoint_id')) {
                $table->dropIndex('erp_credentials_endpoint_active_idx');
                $table->dropConstrainedForeignId('endpoint_id');
            }
        });
    }
};
