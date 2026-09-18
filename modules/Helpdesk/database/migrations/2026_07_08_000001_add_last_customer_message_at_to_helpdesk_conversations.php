<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timestamp del último mensaje ENTRANTE del cliente, necesario para calcular la
 * ventana de servicio de 24h de WhatsApp (solo la reabren los mensajes del
 * cliente; `last_message_at` no sirve porque incluye también los del agente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->timestamp('last_customer_message_at')->nullable()->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropColumn('last_customer_message_at');
        });
    }
};
