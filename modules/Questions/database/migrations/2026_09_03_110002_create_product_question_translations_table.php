<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traducciones de una consulta, una por idioma.
 *
 * En la tienda ya estaban bien modeladas (product_questions_lang), así que aquí
 * no hay que arreglar nada heredado: solo reflejarlas y poder revisarlas.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('product_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('product_questions')->cascadeOnDelete();
            $table->unsignedInteger('ps_lang_id');
            $table->string('lang_iso', 5);

            $table->text('question')->nullable();
            $table->text('answer')->nullable();

            $table->string('provider', 32)->nullable();
            $table->boolean('inherited')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->unsignedInteger('chars')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['question_id', 'ps_lang_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_question_translations');
    }
};
