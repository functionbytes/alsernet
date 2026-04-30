<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * HIGH PRIORITY INDEXES:
     * - Polymorphic relationship indexes (sender_type, sender_id)
     * - Missing FK indexes on pivot tables
     * - Composite indexes for common filter patterns
     * - Indexes on frequently searched fields
     */
    public function up(): void
    {
        $this->addIndex('chat_conversation_messages', ['status'], 'hd_messages_status');
        $this->addIndex('chat_conversation_messages', ['message_type', 'private'], 'hd_messages_type_private');
        $this->addIndex('chat_customer_label', ['customer_id'], 'hd_customer_label_customer_idx');
        $this->addIndex('chat_inboxes', ['channel_id'], 'hd_inboxes_channel_id');
        $this->addIndex('chat_automations', ['account_id', 'event_name', 'active'], 'hd_automations_account_event_active');
        $this->addIndex('chat_sla_applied_conversations', ['first_response_breached', 'first_response_due_at'], 'hd_sla_fr_breach_due');
        $this->addIndex('chat_sla_applied_conversations', ['resolution_breached', 'resolution_due_at'], 'hd_sla_res_breach_due');
        $this->addIndex('chat_csat_surveys', ['account_id', 'rating', 'responded_at'], 'hd_csat_account_rating_responded');
        $this->addIndex('chat_customer_inboxes', ['source_id'], 'hd_customer_inboxes_source_id');
        $this->addIndex('chat_agent_presence', ['account_id', 'status'], 'hd_presence_account_status');
        $this->addIndex('chat_attributes', ['account_id', 'attribute_model'], 'hd_attributes_account_model');
        $this->addIndex('chat_conversation_sessions', ['last_activity_at'], 'hd_conv_sessions_activity');
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        if ($this->indexNameExists($table, $index)) {
            return;
        }
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    private function indexNameExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        )->c > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('chat_conversation_sessions')) {
            Schema::table('chat_conversation_sessions', function (Blueprint $table) {
                if ($this->hasIndex('chat_conversation_sessions', 'hd_conv_sessions_activity')) {
                    $table->dropIndex('hd_conv_sessions_activity');
                }
            });
        }

        Schema::table('chat_attributes', function (Blueprint $table) {
            $table->dropIndex('hd_attributes_account_model');
        });

        Schema::table('chat_agent_presence', function (Blueprint $table) {
            $table->dropIndex('hd_presence_account_status');
        });

        if (Schema::hasTable('chat_customer_inboxes')) {
            Schema::table('chat_customer_inboxes', function (Blueprint $table) {
                if ($this->hasIndex('chat_customer_inboxes', 'hd_customer_inboxes_source_id')) {
                    $table->dropIndex('hd_customer_inboxes_source_id');
                }
            });
        }

        Schema::table('chat_csat_surveys', function (Blueprint $table) {
            $table->dropIndex('hd_csat_account_rating_responded');
        });

        Schema::table('chat_sla_applied_conversations', function (Blueprint $table) {
            $table->dropIndex('hd_sla_fr_breach_due');
            $table->dropIndex('hd_sla_res_breach_due');
        });

        Schema::table('chat_automations', function (Blueprint $table) {
            $table->dropIndex('hd_automations_account_event_active');
        });

        if (Schema::hasTable('chat_inboxes')) {
            Schema::table('chat_inboxes', function (Blueprint $table) {
                if ($this->hasIndex('chat_inboxes', 'hd_inboxes_channel_id')) {
                    $table->dropIndex('hd_inboxes_channel_id');
                }
            });
        }

        if (Schema::hasTable('chat_customer_label')) {
            Schema::table('chat_customer_label', function (Blueprint $table) {
                if ($this->hasIndex('chat_customer_label', 'hd_customer_label_customer_idx')) {
                    $table->dropIndex('hd_customer_label_customer_idx');
                }
            });
        }

        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->dropIndex('hd_messages_type_private');
            $table->dropIndex('hd_messages_status');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        $rows = DB::select(
            'SELECT DISTINCT column_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = ?',
            [$database, $table]
        );

        foreach ($rows as $row) {
            $name = $row->column_name ?? $row->COLUMN_NAME ?? null;
            if ($name === $column) {
                return true;
            }
        }

        return false;
    }
};
