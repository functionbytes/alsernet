<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte los recordatorios sueltos en una secuencia.
 *
 * Hasta ahora un followup era un aviso aislado: "recuérdame este ticket el
 * día X". Lo que hace falta al esperar documentación de un cliente es una
 * cadena — recordar a los 2 días, luego a los 5 — que se corte sola en cuanto
 * el cliente conteste, porque si no el agente recibe avisos de algo ya
 * resuelto.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return 'helpdesk';
    }

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_followups', function (Blueprint $table) {
            // Orden dentro de la secuencia (1, 2, 3…). Los recordatorios
            // sueltos que ya existen quedan como paso 1.
            $table->unsignedTinyInteger('step')->default(1)->after('note');
            // Condición de parada del mockup: "detener la secuencia si el
            // cliente responde".
            $table->boolean('cancel_if_customer_replies')->default(true)->after('step');
            $table->timestamp('cancelled_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_followups', function (Blueprint $table) {
            $table->dropColumn(['step', 'cancel_if_customer_replies', 'cancelled_at']);
        });
    }
};
