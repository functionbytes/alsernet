<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de coincidencias de la lista negra: cada vez que
 * TicketEmailBlacklist::matches() bloquea un correo entrante se guarda una
 * fila aquí (regla que lo bloqueó, remitente exacto, asunto). Separado de
 * helpdesk_ticket_email_blacklist.matched_count/last_matched_at, que solo
 * llevan el agregado — esta tabla es el detalle consultable desde el panel
 * "Historial" que no existía hasta ahora (los correos bloqueados solo
 * quedaban en storage/logs/laravel-*.log, no navegable ni persistente).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ticket_email_blacklist_hits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blacklist_id');
            $table->string('from_email');
            $table->string('subject')->nullable();
            $table->timestamps();

            $table->index('blacklist_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_email_blacklist_hits');
    }
};
