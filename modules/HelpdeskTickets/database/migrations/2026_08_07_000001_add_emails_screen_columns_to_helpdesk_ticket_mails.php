<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            // ticket_item_id existe en la BD (usada por SendCustomerReplyNotification)
            // pero nunca tuvo migración propia — drift de esquema; se añade aquí
            // guardado para no reventar en un entorno que sí parta de cero.
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_mails', 'ticket_item_id')) {
                $table->unsignedBigInteger('ticket_item_id')->nullable()->after('ticket_comment_id');
                $table->index('ticket_item_id');
            }

            // Quién envió el correo. Sin FK real: users vive en la conexión
            // por defecto (mariadb), no en 'helpdesk' — mismo patrón que
            // helpdesk_tickets.assignee_id.
            $table->unsignedBigInteger('user_id')->nullable()->after('ticket_id');
            $table->index('user_id');

            // Categoría del correo (independiente de la del ticket: p.ej. un
            // aviso interno de escalado puede ir en categoría distinta).
            $table->unsignedBigInteger('category_id')->nullable()->after('user_id');
            $table->foreign('category_id')->references('id')->on('helpdesk_ticket_categories')->onDelete('set null');
            $table->index('category_id');

            $table->json('tags')->nullable()->after('category_id');

            // Envío programado: null = inmediato/ya enviado. Se rellena al
            // "Programar" desde el composer; el comando de envío programado
            // lo consulta y lo limpia al entregar.
            $table->timestamp('scheduled_at')->nullable()->after('delivered_at');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        // ticket_item_id se deja intacto: en la práctica ya existía antes de
        // esta migración (drift preexistente) en todos los entornos salvo uno
        // recién creado, y no es seguro asumir aquí quién lo añadió.
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['user_id', 'category_id', 'tags', 'scheduled_at']);
        });
    }
};
