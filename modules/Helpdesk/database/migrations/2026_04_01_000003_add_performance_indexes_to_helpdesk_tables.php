<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Index for filtering draft/published articles (very frequent query)
        if (Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_articles') && Schema::connection($this->connection)->hasColumn('helpdesk_helpcenter_articles', 'draft')) {
            Schema::connection($this->connection)->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_helpcenter_articles', 'helpcenter_articles_draft_index')) {
                    $table->index('draft', 'helpcenter_articles_draft_index');
                }
                if (! $this->hasIndex('helpdesk_helpcenter_articles', 'helpcenter_articles_section_draft_index')) {
                    $table->index(['section_id', 'draft'], 'helpcenter_articles_section_draft_index');
                }
            });
        }

        // Index for AI agent session status filtering
        if (Schema::connection($this->connection)->hasTable('helpdesk_ai_agent_sessions')) {
            Schema::connection($this->connection)->table('helpdesk_ai_agent_sessions', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_ai_agent_sessions', 'ai_sessions_agent_status_index')) {
                    $table->index(['agent_id', 'status'], 'ai_sessions_agent_status_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_articles')) {
            Schema::connection($this->connection)->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
                $table->dropIndexIfExists('helpcenter_articles_draft_index');
                $table->dropIndexIfExists('helpcenter_articles_section_draft_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_ai_agent_sessions')) {
            Schema::connection($this->connection)->table('helpdesk_ai_agent_sessions', function (Blueprint $table) {
                $table->dropIndexIfExists('ai_sessions_agent_status_index');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
