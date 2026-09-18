<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agent_flows')) {
            return;
        }

        if ($schema->hasColumn('helpdesk_ai_agent_flows', 'trigger_type')
            && ! $schema->hasColumn('helpdesk_ai_agent_flows', 'trigger')) {
            $schema->table('helpdesk_ai_agent_flows', function (Blueprint $table) {
                $table->renameColumn('trigger_type', 'trigger');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agent_flows')) {
            return;
        }

        if ($schema->hasColumn('helpdesk_ai_agent_flows', 'trigger')
            && ! $schema->hasColumn('helpdesk_ai_agent_flows', 'trigger_type')) {
            $schema->table('helpdesk_ai_agent_flows', function (Blueprint $table) {
                $table->renameColumn('trigger', 'trigger_type');
            });
        }
    }
};
