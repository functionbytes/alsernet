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

        if (! $schema->hasTable('helpdesk_ai_agent_sessions')) {
            return;
        }

        $schema->table('helpdesk_ai_agent_sessions', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('helpdesk_ai_agent_sessions', 'tokens_used')) {
                $table->unsignedInteger('tokens_used')->default(0)->after('ended_at');
            }

            if (! $schema->hasColumn('helpdesk_ai_agent_sessions', 'cost_usd')) {
                $table->decimal('cost_usd', 10, 6)->default(0)->after('tokens_used');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agent_sessions')) {
            return;
        }

        $schema->table('helpdesk_ai_agent_sessions', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('helpdesk_ai_agent_sessions', 'cost_usd')) {
                $table->dropColumn('cost_usd');
            }

            if ($schema->hasColumn('helpdesk_ai_agent_sessions', 'tokens_used')) {
                $table->dropColumn('tokens_used');
            }
        });
    }
};
