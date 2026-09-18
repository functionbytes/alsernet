<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Representación vectorial del texto de cada ticket, para responder dos
 * preguntas que hoy no tienen respuesta automática:
 *
 *  - «¿esto ya lo tenemos abierto?» (duplicados)
 *  - «¿esto le está pasando a mucha gente a la vez?» (incidencia masiva)
 *
 * Tabla aparte y no columnas en helpdesk_tickets a propósito: el vector es un
 * dato derivado y voluminoso (1536 floats con text-embedding-3-small) que se
 * regenera solo, y no tiene por qué viajar en cada SELECT de la tabla de
 * tickets, que es la más consultada del módulo.
 *
 * `vector_norm` se precalcula al indexar porque la similitud coseno se resuelve
 * en PHP: recalcular la norma de cada candidato en cada búsqueda es el coste
 * que ya se documentó en KnowledgeRetrievalService.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_embeddings')) {
            return;
        }

        $schema->create('helpdesk_ticket_embeddings', function (Blueprint $table) {
            $table->id();

            // unique: un ticket tiene como mucho un vector vigente. Al
            // reindexar se sobrescribe la fila, no se acumulan versiones.
            $table->unsignedBigInteger('ticket_id')->unique();

            $table->longText('embedding');
            $table->double('vector_norm')->default(0);

            // El modelo forma parte del dato: vectores de modelos distintos no
            // son comparables entre sí, así que un cambio de modelo obliga a
            // reindexar y esta columna es lo que permite detectarlo.
            $table->string('embedding_model', 64);

            // Se copian del ticket para poder acotar la búsqueda sin join:
            // los duplicados se buscan dentro de la misma categoría y ventana
            // temporal, y ese filtro tiene que ser barato.
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            $table->timestamps();

            $table->index(['created_at', 'category_id'], 'hd_ticket_emb_recent_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_embeddings');
    }
};
