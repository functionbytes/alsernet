<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_conversations')) {
            $schema->table('helpdesk_conversations', function (Blueprint $t) use ($schema) {
                if ($schema->hasColumn('helpdesk_conversations', 'last_message_at')
                    && ! $this->indexExists('helpdesk_conversations', 'helpdesk_conversations_last_message_at_index')) {
                    $t->index('last_message_at');
                }
                if ($schema->hasColumn('helpdesk_conversations', 'updated_at')
                    && ! $this->indexExists('helpdesk_conversations', 'helpdesk_conversations_updated_at_index')) {
                    $t->index('updated_at');
                }
            });
        }

        if ($schema->hasTable('helpdesk_campaign_impressions')) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $t) use ($schema) {
                if ($schema->hasColumn('helpdesk_campaign_impressions', 'clicked_at')
                    && ! $this->indexExists('helpdesk_campaign_impressions', 'helpdesk_campaign_impressions_clicked_at_index')) {
                    $t->index('clicked_at');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::connection($this->connection)
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($result) > 0;
    }

    public function down(): void {}
};
