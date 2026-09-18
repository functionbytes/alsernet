<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ChatFlow::TRIGGER_TYPES incluye 'intent' (usado activamente en
 * ChatFlowTriggerResolver para disparo por deteccion de intencion), pero el
 * ENUM de la BD nunca se amplio para incluirlo — cualquier flujo con este
 * trigger_type fallaba al guardarse con "Data truncated for column
 * trigger_type".
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_chat_flows')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_chat_flows
             MODIFY COLUMN trigger_type ENUM('conversation_start', 'keyword', 'manual', 'no_agent', 'intent')
             NOT NULL DEFAULT 'conversation_start'"
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_chat_flows')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_chat_flows
             MODIFY COLUMN trigger_type ENUM('conversation_start', 'keyword', 'manual', 'no_agent')
             NOT NULL DEFAULT 'conversation_start'"
        );
    }
};
