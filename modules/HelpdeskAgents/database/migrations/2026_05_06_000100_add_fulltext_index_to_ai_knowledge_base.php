<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agent_knowledge_base', function (Blueprint $t) {
            $t->fullText(['title', 'content'], 'helpdesk_ai_agent_knowledge_base_fulltext');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agent_knowledge_base', function (Blueprint $t) {
            $t->dropFullText('helpdesk_ai_agent_knowledge_base_fulltext');
        });
    }
};
