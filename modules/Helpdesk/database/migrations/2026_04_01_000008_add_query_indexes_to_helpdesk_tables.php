<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
                // category_id — used in byCategory scope
                if (! $this->hasIndex('helpdesk_tickets', 'helpdesk_tickets_category_id_index')) {
                    $table->index('category_id', 'helpdesk_tickets_category_id_index');
                }
                // first_response_at — used in SLA calculations
                if (! $this->hasIndex('helpdesk_tickets', 'helpdesk_tickets_first_response_at_index')) {
                    $table->index('first_response_at', 'helpdesk_tickets_first_response_at_index');
                }
                // resolved_at — used in resolved scope
                if (! $this->hasIndex('helpdesk_tickets', 'helpdesk_tickets_resolved_at_index')) {
                    $table->index('resolved_at', 'helpdesk_tickets_resolved_at_index');
                }
                // (sla_first_response_breached, status_id) composite — SLA breach + open ticket queries
                if (! $this->hasIndex('helpdesk_tickets', 'helpdesk_tickets_sla_breach_status_index')) {
                    $table->index(['sla_first_response_breached', 'status_id'], 'helpdesk_tickets_sla_breach_status_index');
                }
                // sla_first_response_due_at — SLA warning queries
                if (! $this->hasIndex('helpdesk_tickets', 'helpdesk_tickets_sla_first_response_due_at_index')) {
                    $table->index('sla_first_response_due_at', 'helpdesk_tickets_sla_first_response_due_at_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations') && Schema::connection($this->connection)->hasColumn('helpdesk_conversations', 'channel')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_channel_index')) {
                    $table->index('channel', 'helpdesk_conversations_channel_index');
                }
                if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_sender_channel_index')) {
                    $table->index(['external_sender_id', 'channel'], 'helpdesk_conversations_sender_channel_index');
                }
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_conversation_created_index')) {
                    $table->index(['conversation_id', 'created_at'], 'helpdesk_conv_items_conversation_created_index');
                }
                if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'external_id') &&
                    ! $this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_external_id_index')) {
                    $table->index('external_id', 'helpdesk_conv_items_external_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_tickets_category_id_index');
                $table->dropIndexIfExists('helpdesk_tickets_first_response_at_index');
                $table->dropIndexIfExists('helpdesk_tickets_resolved_at_index');
                $table->dropIndexIfExists('helpdesk_tickets_sla_breach_status_index');
                $table->dropIndexIfExists('helpdesk_tickets_sla_first_response_due_at_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conversations_channel_index');
                $table->dropIndexIfExists('helpdesk_conversations_sender_channel_index');
            });
        }

        if (Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_conv_items_conversation_created_index');
                $table->dropIndexIfExists('helpdesk_conv_items_external_id_index');
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
