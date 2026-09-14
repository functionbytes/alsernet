<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traducciones de una opinión, una por idioma de destino.
 *
 * La clave única sobre (review_id, ps_lang_id) es lo que hace imposible repetir
 * el fallo que dejó 32 filas de una sola opinión: por mucho que se relance el
 * proceso, cada idioma solo puede tener una.
 *
 * Nace vacía a propósito: la traducción automática está desactivada.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('product_review_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->unsignedInteger('ps_lang_id');
            $table->string('lang_iso', 5);

            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('answer')->nullable();

            // De dónde salió y cuánto costó, para poder auditar el gasto
            $table->string('provider', 32)->nullable();
            $table->unsignedInteger('chars')->default(0);
            $table->boolean('reviewed')->default(false);

            // Identificador de la fila equivalente en PrestaShop
            $table->unsignedInteger('ps_comment_id')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['review_id', 'ps_lang_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_review_translations');
    }
};
