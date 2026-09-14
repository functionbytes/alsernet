<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_priority_index')) {
                    $table->index('priority', 'helpdesk_conversations_priority_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_closed_at_index')) {
                    $table->index('closed_at', 'helpdesk_conversations_closed_at_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_last_message_at_index')) {
                    $table->index('last_message_at', 'helpdesk_conversations_last_message_at_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_assignee_closed_index')) {
                    $table->index(['assignee_id', 'closed_at'], 'helpdesk_conversations_assignee_closed_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_archived_status_index')) {
                    $table->index(['is_archived', 'status_id'], 'helpdesk_conversations_archived_status_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_customers', 'helpdesk_customers_phone_index')) {
                    $table->index('phone', 'helpdesk_customers_phone_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conversations_priority_index');
                $table->dropIndexIfExists('helpdesk_conversations_closed_at_index');
                $table->dropIndexIfExists('helpdesk_conversations_last_message_at_index');
                $table->dropIndexIfExists('helpdesk_conversations_assignee_closed_index');
                $table->dropIndexIfExists('helpdesk_conversations_archived_status_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_customers_phone_index');
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
