<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Cancelar si el cliente responde antes" del modal "Programar envío".
 *
 * Un email programado que ya no tiene sentido —porque el cliente escribió
 * mientras tanto y probablemente resolvió lo que se le iba a preguntar— se
 * descarta en vez de salir igualmente y confundirle. El comando
 * SendScheduledTicketMailsCommand comprueba esta bandera justo antes de
 * entregar, comparando contra el último mensaje ENTRANTE del ticket.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->boolean('cancel_if_customer_replies')->default(false)->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropColumn('cancel_if_customer_replies');
        });
    }
};
