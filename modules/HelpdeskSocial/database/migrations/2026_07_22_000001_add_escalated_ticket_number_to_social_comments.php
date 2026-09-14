<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad + idempotencia del escalado social → ticket: guarda el número del
 * ticket creado al escalar un comentario, para no crear un segundo si el
 * comentario se escala de nuevo y para navegar del comentario al ticket.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('helpdesk_social_comments', 'escalated_ticket_number')) {
            Schema::table('helpdesk_social_comments', function (Blueprint $table) {
                $table->string('escalated_ticket_number')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('helpdesk_social_comments', 'escalated_ticket_number')) {
            Schema::table('helpdesk_social_comments', function (Blueprint $table) {
                $table->dropColumn('escalated_ticket_number');
            });
        }
    }
};
