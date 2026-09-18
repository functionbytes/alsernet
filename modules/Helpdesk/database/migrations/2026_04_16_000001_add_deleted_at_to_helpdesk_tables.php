<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private array $tables = [
        'helpdesk_customers',
        'helpdesk_ai_agents',
        'helpdesk_ai_agent_flows',
        'helpdesk_ai_agent_knowledge_base',
        'helpdesk_ai_agent_tags',
        'helpdesk_ai_agent_tools',
        'helpdesk_conversation_tags',
        'helpdesk_helpcenter_categories',
        'helpdesk_ticket_comments',
        'helpdesk_ticket_mails',
        'helpdesk_ticket_notes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            $hasTable = Schema::connection($this->connection)->hasTable($tableName);
            $hasColumn = $hasTable && Schema::connection($this->connection)->hasColumn($tableName, 'deleted_at');

            if ($hasTable && ! $hasColumn) {
                Schema::connection($this->connection)->table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            $hasTable = Schema::connection($this->connection)->hasTable($tableName);
            $hasColumn = $hasTable && Schema::connection($this->connection)->hasColumn($tableName, 'deleted_at');

            if ($hasTable && $hasColumn) {
                Schema::connection($this->connection)->table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
