<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `helpdesk_ai_agent_sessions` solo tenía índices simples en `conversation_id`
 * y `ai_agent_id` (migración 2026_04_01_000005) — pero la consulta de alta
 * frecuencia (StartAiAgentSessionOnIncomingMessage, en el camino síncrono de
 * CADA mensaje entrante de cualquier canal) filtra por
 * `conversation_id + status = 'active'`. Sin un índice compuesto, MySQL/
 * MariaDB resuelve por `conversation_id` y filtra `status` en memoria fila a
 * fila — barato por conversación individual, pero es la query de mayor
 * frecuencia del módulo. Mismo criterio que ya tiene
 * helpdesk_chat_flow_sessions (conversation_id, status).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agent_sessions', function (Blueprint $table) {
            $table->index(['conversation_id', 'status'], 'hais_conversation_status_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agent_sessions', function (Blueprint $table) {
            $table->dropIndex('hais_conversation_status_idx');
        });
    }
};
