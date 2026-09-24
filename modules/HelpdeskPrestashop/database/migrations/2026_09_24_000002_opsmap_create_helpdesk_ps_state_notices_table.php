<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos al cambiar el estado de un pedido desde el workspace del chat
 * (Ajustes → Helpdesk · PrestaShop → Avisos de cambio de estado): por cada
 * estado de PrestaShop, si "Notificar al cliente" sale marcado por defecto y
 * un aviso libre para el agente. Sin fila = comportamiento por defecto.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ps_state_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ps_state_id')->unique();
            $table->string('ps_state_name')->nullable();
            // null = según PrestaShop (marcado si el estado envía correo).
            $table->boolean('notify_default')->nullable();
            $table->string('agent_notice', 500)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ps_state_notices');
    }
};
