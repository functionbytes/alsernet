<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TicketService::closeTicket()/TicketLifecycleController::close() ya
 * aceptaban un $reason, pero solo lo escribían en el log — nunca se
 * guardaba en el ticket. El modal "Cerrar ticket" del mockup pide motivo
 * en un grid de 4 opciones; sin columna no había dónde persistirlo.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets') && ! $schema->hasColumn('helpdesk_tickets', 'close_reason')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->string('close_reason', 100)->nullable()->after('closed_at');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_tickets') && $schema->hasColumn('helpdesk_tickets', 'close_reason')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->dropColumn('close_reason');
            });
        }
    }
};
