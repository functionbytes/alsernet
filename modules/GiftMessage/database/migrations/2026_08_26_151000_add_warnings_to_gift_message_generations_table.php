<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            // Mensajes que no salieron como deberian (letra por debajo de la
            // configurada o texto que no cabe ni al minimo), para poder verlo en
            // el historial y no solo en el momento de generar.
            $table->json('warnings')->nullable()->after('rows');
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            $table->dropColumn('warnings');
        });
    }
};
