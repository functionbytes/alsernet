<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private array $tables = [
        'engagement_trigger_rules',
        'engagement_personalization_rules',
        'engagement_automation_flows',
        'engagement_conversion_goals',
        'engagement_platform_integrations',
        'engagement_web_channels',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::connection('helpdesk')->hasTable($tableName)) {
                continue;
            }

            Schema::connection('helpdesk')->table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::connection('helpdesk')->hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::connection('helpdesk')->hasTable($tableName)) {
                continue;
            }

            Schema::connection('helpdesk')->table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::connection('helpdesk')->hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
