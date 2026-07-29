<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'supplier_source_webhooks',
        'supplier_source_monitors',
        'supplier_product_prices',
        'supplier_automation_triggers',
        'supplier_source_transformations',
        'supplier_sync_statuses',
        'supplier_source_templates',
        'supplier_credentials',
        'supplier_automation_executions',
        'supplier_ai_contents',
        'supplier_source_configurations',
        'supplier_automation_chain_executions',
        'supplier_sync_logs',
        'supplier_automation_chains',
        'supplier_sync_conflicts',
        'supplier_automation_variables',
        'supplier_prompt_experiments',
        'supplier_sync_schedules',
        'supplier_extraction_mappings',
        'supplier_sync_failures',
        'supplier_extraction_results',
        'supplier_sync_batches',
        'supplier_automation_workflows',
        'supplier_sync_audit',
        'supplier_product_chats',
        'supplier_extraction_batches',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `uid` char(36) NOT NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `uid` char(26) NOT NULL");
        }
    }
};
