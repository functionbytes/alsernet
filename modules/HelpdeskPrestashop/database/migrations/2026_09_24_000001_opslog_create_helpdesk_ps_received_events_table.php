<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos de PrestaShop recibidos por el webhook del módulo (pieza 38
 * "Eventos recibidos"). PsEventReceiverController los despacha sin guardarlos;
 * el listener OpslogRecordReceivedEvent deja aquí una fila por evento.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ps_received_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 64);
            // Misma clave de deduplicación que usa el receptor (cabecera de
            // idempotencia o md5 de timestamp+firma): un reintento de PS que
            // llega tras un 500 no crea una segunda fila.
            $table->string('dedup_key', 64)->nullable()->unique();
            $table->string('subject_type', 16)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('ps_customer_id')->nullable();
            $table->string('email', 191)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            // processed = aplicado a un cliente del helpdesk (o evento sin
            // cliente, como los de producto); pending = sin cliente vinculado.
            $table->string('status', 16)->default('processed');
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('reprocess_count')->default(0);
            $table->timestamp('reprocessed_at')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'received_at']);
            $table->index('received_at');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ps_received_events');
    }
};
