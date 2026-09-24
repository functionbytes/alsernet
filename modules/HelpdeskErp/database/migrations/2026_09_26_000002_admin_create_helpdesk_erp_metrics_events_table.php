<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | «Métricas de Gestión»: un evento ligero por petición del panel de Gestión
 | en el chat (overview, sección, pedido, albarán, factura) y por llamada HTTP
 | al manager hecha durante ellas. Se escribe tras enviar la respuesta y se
 | purga a diario según la retención (helpdeskerp:purge-metrics).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_erp_metrics_events')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_erp_metrics_events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('kind', 20);
            $table->string('section', 40)->nullable();
            $table->string('state', 20)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('blocked_count')->default(0);

            $table->index('occurred_at');
            $table->index(['kind', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_erp_metrics_events');
    }
};
