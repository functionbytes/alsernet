<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Mapeo de estados de pedido de PrestaShop → estado de la conversación
 | (pieza 39). Tabla propia en vez de Setting::get/set: esos ajustes tienen
 | problemas de caché conocidos (la caché sobrevive a la transacción de los
 | tests y memoiza el default del primer llamador), y aquí el valor lo lee un
 | listener en cada webhook.
 |
 | Una fila por estado de PS con acción distinta de "sin acción"; ps_state_id
 | = 0 es la fila de ajustes generales (crear nota), para no abrir una
 | segunda tabla por un solo booleano.
 */
return new class extends Migration
{
    // Misma conexión que el resto de tablas helpdesk_* del módulo
    // (helpdesk_assisted_carts, helpdesk_ps_received_events).
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ps_state_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ps_state_id')->unique();
            // Nombre del estado al guardar: la pantalla lo muestra aunque el
            // bridge no responda en ese momento.
            $table->string('ps_state_name')->nullable();
            $table->string('action', 16)->default('none');
            $table->boolean('create_note')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ps_state_map');
    }
};
