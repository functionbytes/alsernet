<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultas de producto.
 *
 * La decisión que cambia el sentido de esta pantalla: una consulta sin respuesta
 * no se publica. De las 56.213 que hay en la tienda, 13.199 están respondidas y
 * sin aprobar — el trabajo hecho y sin llegar al cliente— y 49.617 esperan sin
 * revisar. Por eso `answer` pesa tanto como el estado.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('product_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('ps_question_id')->unique();
            $table->unsignedInteger('ps_product_id')->index();
            $table->unsignedInteger('ps_lang_id');
            $table->string('lang_iso', 5);

            $table->text('question');
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();

            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_reference', 64)->nullable();

            // pending | approved | rejected
            $table->string('status', 16)->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();

            // Estado en la tienda, para ver desfases entre los dos paneles
            $table->boolean('ps_approved')->default(false);
            $table->timestamp('ps_date')->nullable();
            $table->timestamp('ps_date_upd')->nullable();

            $table->timestamp('translated_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('ps_approved');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_questions');
    }
};
