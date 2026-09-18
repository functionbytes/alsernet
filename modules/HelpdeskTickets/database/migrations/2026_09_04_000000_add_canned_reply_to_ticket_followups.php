<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Secuencia de seguimiento" del mockup (modal 18, ve-mail-sequence) trae un
 * selector "Plantilla" — el paso programado no solo recuerda al agente que
 * revise el ticket, también manda un correo REAL al cliente con esa
 * plantilla. Antes no había dónde guardar cuál (SendDueTicketFollowupsCommand
 * solo notificaba internamente al agente que programó el paso, nunca al
 * cliente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_followups', function (Blueprint $table) {
            $table->unsignedBigInteger('canned_reply_id')->nullable()->after('note');
            $table->foreign('canned_reply_id')
                ->references('id')->on('helpdesk_ticket_canned_replies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_followups', function (Blueprint $table) {
            $table->dropForeign(['canned_reply_id']);
            $table->dropColumn('canned_reply_id');
        });
    }
};
