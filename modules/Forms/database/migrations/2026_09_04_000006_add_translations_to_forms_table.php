<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traducciones a nivel de formulario (hoy: el mensaje de éxito y su nota).
 *
 * Los campos ya tenían su `translations`, así que el formulario se servía en
 * los seis idiomas de la tienda... hasta que se enviaba: el aviso posterior
 * salía en español para todos, porque `success_message` es una sola cadena.
 *
 * Misma forma anidada que en form_fields: ['en' => ['success_message' => ...]].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('success_message');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
