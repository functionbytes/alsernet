<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ConversationSlaBreach::unresolved() (usado en cada carga de
 * SlaBreachesController::data()) filtra solo por 'resolved', pero el unico
 * indice compuesto de la tabla es (sla_type, resolved) — con sla_type como
 * columna lider, un filtro libre por 'resolved' no puede aprovecharlo del
 * todo. Se añade un indice simple para ese caso de uso.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversation_sla_breaches', function (Blueprint $table) {
            $table->index('resolved', 'helpdesk_conversation_sla_breaches_resolved_index');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversation_sla_breaches', function (Blueprint $table) {
            $table->dropIndex('helpdesk_conversation_sla_breaches_resolved_index');
        });
    }
};
