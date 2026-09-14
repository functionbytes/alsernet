<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado de publicación de cada formulario en la tienda.
 *
 * `published_hash` es lo que permite responder "¿hay cambios sin publicar?" sin
 * preguntarle nada a PrestaShop: se compara con el hash del artefacto que
 * produce FormArtifactBuilder en este momento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_prestashop_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('form_key', 60);

            $table->unsignedInteger('published_version')->default(0);
            $table->string('published_hash', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            // pending: encolado; published: la tienda confirmó; failed: agotó reintentos
            $table->enum('status', ['pending', 'published', 'failed', 'unpublished'])->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamps();

            // Un formulario ocupa una form_key y solo una.
            $table->unique('form_id');
            $table->unique('form_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_prestashop_publications');
    }
};
