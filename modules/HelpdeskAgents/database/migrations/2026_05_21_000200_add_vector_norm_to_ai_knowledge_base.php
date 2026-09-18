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

        if (! $schema->hasTable('helpdesk_ai_agent_knowledge_base')) {
            return;
        }

        if ($schema->hasColumn('helpdesk_ai_agent_knowledge_base', 'vector_norm')) {
            return;
        }

        $schema->table('helpdesk_ai_agent_knowledge_base', function (Blueprint $table): void {
            $table->float('vector_norm', 8, 6)->nullable()->after('embedding_model');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agent_knowledge_base')) {
            return;
        }

        if (! $schema->hasColumn('helpdesk_ai_agent_knowledge_base', 'vector_norm')) {
            return;
        }

        $schema->table('helpdesk_ai_agent_knowledge_base', function (Blueprint $table): void {
            $table->dropColumn('vector_norm');
        });
    }
};
