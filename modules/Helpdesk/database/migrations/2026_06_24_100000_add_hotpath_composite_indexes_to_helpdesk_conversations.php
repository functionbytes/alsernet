<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Composite indexes for the hottest helpdesk inbox queries that the existing
     * single-column / partial composites do not fully cover.
     *
     * Hot-path queries (ConversationsController@index + ConversationFilter):
     *   - Status tab + sort: WHERE status_id = ? AND deleted_at IS NULL
     *     ORDER BY last_message_at DESC   -> needs (status_id, deleted_at, last_message_at)
     *   - Inbox + status tab: WHERE inbox_id IN (..) AND status_id = ?
     *     ORDER BY last_message_at DESC   -> needs (inbox_id, status_id, last_message_at)
     *   - Group filter + sort: WHERE group_id = ? AND deleted_at IS NULL
     *     ORDER BY last_message_at DESC   -> needs (group_id, deleted_at, last_message_at)
     *   - "Mine" filter + sort: WHERE assignee_id = ? AND deleted_at IS NULL
     *     ORDER BY last_message_at DESC   -> needs (assignee_id, deleted_at, last_message_at)
     *   - incoming_messages_count withCount: WHERE conversation_id = ?
     *     AND type = 'message' AND user_id IS NULL
     *     on helpdesk_conversation_items  -> needs (conversation_id, type, user_id)
     *   - "unread" filter whereDoesntHave('reads', user_id = ?):
     *     on helpdesk_conversation_reads  -> needs (user_id, conversation_id)
     *
     * Only indexes NOT already present are added (idempotent via hasIndex()).
     * Semantics of the queries are unchanged; these are read-path indexes only.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_status_deleted_last_message_index')) {
                    $table->index(['status_id', 'deleted_at', 'last_message_at'], 'helpdesk_conversations_status_deleted_last_message_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_inbox_status_last_message_index')) {
                    $table->index(['inbox_id', 'status_id', 'last_message_at'], 'helpdesk_conversations_inbox_status_last_message_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_group_deleted_last_message_index')) {
                    $table->index(['group_id', 'deleted_at', 'last_message_at'], 'helpdesk_conversations_group_deleted_last_message_index');
                }

                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_assignee_deleted_last_message_index')) {
                    $table->index(['assignee_id', 'deleted_at', 'last_message_at'], 'helpdesk_conversations_assignee_deleted_last_message_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_conversation_type_user_index')) {
                    $table->index(['conversation_id', 'type', 'user_id'], 'helpdesk_conv_items_conversation_type_user_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_reads')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_reads', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversation_reads', 'helpdesk_conversation_reads_user_conversation_index')) {
                    $table->index(['user_id', 'conversation_id'], 'helpdesk_conversation_reads_user_conversation_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conversations_status_deleted_last_message_index');
                $table->dropIndexIfExists('helpdesk_conversations_inbox_status_last_message_index');
                $table->dropIndexIfExists('helpdesk_conversations_group_deleted_last_message_index');
                $table->dropIndexIfExists('helpdesk_conversations_assignee_deleted_last_message_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conv_items_conversation_type_user_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_reads')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_reads', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conversation_reads_user_conversation_index');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection($this->connection)->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
