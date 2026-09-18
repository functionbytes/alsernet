<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza cada nota interna con el mensaje que la representa en el hilo.
 *
 * Había dos formas de escribir una nota interna y no se comportaban igual: la
 * del composer del hilo crea un helpdesk_ticket_items con is_internal=1 y se ve
 * en la conversación; la de la tarjeta "Nota interna" del panel lateral creaba
 * solo un helpdesk_ticket_notes (con color y chincheta) que únicamente aparecía
 * en "Notas del ticket". Para el agente eran la misma acción con dos resultados
 * distintos.
 *
 * Con esta columna, la nota del panel crea además su mensaje en el hilo y sabe
 * cuál es, así que al borrarla o editarla el hilo no se queda con un huérfano.
 * Nullable porque las notas anteriores a este cambio no tienen mensaje asociado.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_notes')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('helpdesk_ticket_notes', 'ticket_item_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ticket_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_item_id')->nullable()->after('ticket_id');
            $table->index('ticket_item_id', 'helpdesk_ticket_notes_item_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_notes')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ticket_notes', function (Blueprint $table) {
            $table->dropIndex('helpdesk_ticket_notes_item_idx');
            $table->dropColumn('ticket_item_id');
        });
    }
};
