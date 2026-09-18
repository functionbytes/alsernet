<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo estructurado conversación → ticket. Hasta ahora el origen de un ticket
 * creado desde una conversación solo quedaba como texto en una nota interna;
 * esta columna lo hace navegable en ambos sentidos (ticket ↔ conversación de
 * origen) y habilita el flujo omnicanal (ChatFlow/Social abren tickets trazables).
 *
 * Sin FK constraint dura: mismo criterio que customer_id (ambas tablas viven en
 * la conexión helpdesk pero el proyecto no declara constraints entre ellas). El
 * ticket debe sobrevivir al borrado GDPR de su conversación, conservando la
 * trazabilidad hasta que se redacte por su propia cascada de compliance.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'conversation_id')) {
                $table->unsignedBigInteger('conversation_id')->nullable()->after('customer_id');
                $table->index('conversation_id', 'helpdesk_tickets_conversation_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'conversation_id')) {
                $table->dropIndex('helpdesk_tickets_conversation_id_index');
                $table->dropColumn('conversation_id');
            }
        });
    }
};
