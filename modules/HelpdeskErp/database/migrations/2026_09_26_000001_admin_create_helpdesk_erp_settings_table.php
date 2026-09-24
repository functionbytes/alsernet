<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | «Ajustes de Gestión» (extensión "admin" de HelpdeskErp): TTL de caché por
 | estado, pedidos del resumen, avisos, vinculación automática, plantillas de
 | seguimiento y retención de métricas, editables sin despliegue. Una fila por
 | ajuste (clave lógica) con su valor en JSON; solo existen las que cambian el
 | valor de config/.env.
 |
 | Tabla propia en vez del Setting del core: aquel cachea el default del
 | primer llamador y su caché sobrevive a las transacciones de los tests.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_erp_settings')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_erp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_erp_settings');
    }
};
