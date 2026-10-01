<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * trigger_type era un ENUM y cada tipo nuevo de ChatFlow::TRIGGER_TYPES
 * fallaba al guardarse ("Data truncated for column trigger_type"): pasó con
 * 'intent' y otra vez con 'procedure'. Pasa a VARCHAR; los valores válidos
 * los valida la aplicación (Store/UpdateChatFlowRequest, import).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_chat_flows')
            || DB::connection($this->connection)->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_chat_flows
             MODIFY COLUMN trigger_type VARCHAR(32) NOT NULL DEFAULT 'conversation_start'"
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_chat_flows')
            || DB::connection($this->connection)->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_chat_flows
             MODIFY COLUMN trigger_type ENUM('conversation_start', 'keyword', 'manual', 'no_agent', 'intent')
             NOT NULL DEFAULT 'conversation_start'"
        );
    }
};
