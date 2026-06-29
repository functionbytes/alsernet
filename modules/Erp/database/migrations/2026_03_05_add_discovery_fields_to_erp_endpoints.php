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
        Schema::table('erp_endpoints', function (Blueprint $table) {
            // 'description' already exists in base migration — skip it
            if (! Schema::hasColumn('erp_endpoints', 'controller')) {
                $table->string('controller')->nullable()->after('method');
            }
            if (! Schema::hasColumn('erp_endpoints', 'action')) {
                $table->string('action')->nullable()->after('controller');
            }
            if (! Schema::hasColumn('erp_endpoints', 'parameters')) {
                $table->json('parameters')->nullable()->after('description');
            }
            if (! Schema::hasColumn('erp_endpoints', 'response_example')) {
                $table->json('response_example')->nullable()->after('parameters');
            }
            if (! Schema::hasColumn('erp_endpoints', 'is_auto_discovered')) {
                $table->boolean('is_auto_discovered')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('erp_endpoints', 'deprecated_at')) {
                $table->timestamp('deprecated_at')->nullable()->after('is_auto_discovered');
            }

            $table->index('is_auto_discovered');
            $table->index('deprecated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_endpoints', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('erp_endpoints', 'controller') ? 'controller' : null,
                Schema::hasColumn('erp_endpoints', 'action') ? 'action' : null,
                Schema::hasColumn('erp_endpoints', 'parameters') ? 'parameters' : null,
                Schema::hasColumn('erp_endpoints', 'response_example') ? 'response_example' : null,
                Schema::hasColumn('erp_endpoints', 'is_auto_discovered') ? 'is_auto_discovered' : null,
                Schema::hasColumn('erp_endpoints', 'deprecated_at') ? 'deprecated_at' : null,
            ]));

            $table->dropIndex(['is_auto_discovered']);
            $table->dropIndex(['deprecated_at']);
        });
    }
};
