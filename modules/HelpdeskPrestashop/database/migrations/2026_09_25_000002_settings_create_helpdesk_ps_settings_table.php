<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Ajustes del chat de PrestaShop editables sin despliegue (extensión
 | "settings"): límites de vales y reembolsos, instrucciones de retorno y
 | respuestas rápidas. Una fila por ajuste (clave lógica, p. ej.
 | "vouchers.agent_limit") con su valor en JSON. Solo existen las filas que
 | cambian el valor por defecto de config/.env.
 |
 | Tabla propia en vez del modelo Setting del core: ese cachea el default
 | del primer llamador y su caché sobrevive a las transacciones de los tests.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ps_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ps_settings');
    }
};
