<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BUG-02 (auditoría): sin unicidad real de message_id, un fallo silencioso de
 * $message->setFlag('Seen') en FetchTicketEmailsJob reprocesaba el mismo
 * correo entrante cada minuto — creando tickets duplicados y reenviando la
 * confirmación al cliente una y otra vez. El guard de idempotencia en
 * FetchTicketEmailsJob::processIncomingEmail() ya corta el sangrado en PHP;
 * este índice UNIQUE es el cierre a nivel de esquema (dos escrituras
 * concurrentes con el mismo message_id ya no pueden colarse ambas).
 *
 * message_id no se separa por `direction`: tanto el hilado por Message-ID
 * como la correlación con EmailLog (ver TicketDetailDataController,
 * TicketMailDetailDataController) buscan por message_id solo, sin filtrar
 * por dirección — un inbound y un outbound comparten el mismo espacio de
 * valores por diseño, así que la unicidad real es global, no compuesta.
 *
 * La migración original (2025_12_29_020910) ya corrió en todos los entornos
 * (verificado con `artisan migrate:status`), así que esto va en una
 * migración nueva en vez de editarla.
 *
 * Guard defensivo: si al ejecutar esto en algún entorno con datos reales ya
 * existen duplicados (el propio bug que se está cerrando), la ALTER TABLE
 * fallaría con un error de SQL opaco. Se comprueba antes y se aborta con un
 * mensaje que dice qué hacer, en vez de dejar que MySQL lo reporte como un
 * error genérico de índice.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    private const INDEX = 'helpdesk_ticket_mails_message_id_index';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_mails')) {
            return;
        }

        $duplicates = DB::connection($this->connection)
            ->table('helpdesk_ticket_mails')
            ->select('message_id')
            ->whereNotNull('message_id')
            ->groupBy('message_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'No se puede crear el índice UNIQUE sobre helpdesk_ticket_mails.message_id: hay %d valores de message_id duplicados. '
                .'Limpia los duplicados (quedándote con el registro más antiguo de cada grupo, por ejemplo) antes de re-ejecutar esta migración. Ver BUG-02.',
                $duplicates->count()
            ));
        }

        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            // El UNIQUE ya sirve como índice de búsqueda por message_id — el
            // índice plano anterior queda redundante.
            $table->dropIndex(self::INDEX);
            $table->unique('message_id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_mails')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropUnique(['message_id']);
            $table->index('message_id', self::INDEX);
        });
    }
};
