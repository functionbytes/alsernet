<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisiones de calidad de tickets ya cerrados.
 *
 * Hoy la única señal de calidad del helpdesk es el CSAT, y el CSAT lo responde
 * una minoría — casi siempre la muy contenta o la muy enfadada. El grueso de la
 * atención no deja ni rastro, así que un agente puede estar respondiendo mal
 * durante meses sin que ninguna métrica lo diga.
 *
 * Esto muestrea tickets cerrados al azar y los evalúa, lo que da una medida que
 * NO depende de que el cliente conteste.
 *
 * `dimensions` guarda las puntuaciones por eje (resolución, tono, precisión...)
 * como JSON y no en columnas: los ejes se ajustarán con el uso y una migración
 * por cada cambio de criterio no compensa.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_reviews')) {
            return;
        }

        $schema->create('helpdesk_ticket_reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id')->index();
            // Denormalizado a propósito: el agente puede cambiar de asignación
            // después, y la revisión evalúa a QUIEN atendió el caso entonces.
            $table->unsignedBigInteger('agent_id')->nullable()->index();

            $table->unsignedTinyInteger('score');
            $table->json('dimensions')->nullable();
            $table->text('summary')->nullable();
            $table->json('issues')->nullable();

            // Una persona puede estar en desacuerdo con la revisión. Sin esto,
            // una evaluación automática es un juicio sin apelación sobre el
            // trabajo de alguien.
            $table->boolean('disputed')->default(false);
            $table->text('dispute_note')->nullable();
            $table->unsignedBigInteger('disputed_by')->nullable();

            $table->string('model', 64)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'agent_id'], 'hd_ticket_reviews_period_idx');
            $table->unique(['ticket_id'], 'hd_ticket_reviews_ticket_unique');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_reviews');
    }
};
