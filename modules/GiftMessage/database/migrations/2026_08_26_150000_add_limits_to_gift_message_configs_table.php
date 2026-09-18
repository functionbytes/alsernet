<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            // Suelo de legibilidad: el ajuste automatico no baja de aqui aunque
            // el mensaje no quepa; en ese caso se avisa en vez de imprimir algo
            // que no se puede leer.
            $table->unsignedTinyInteger('min_font_size')->default(7)->after('card_t2_opacity');
            // A partir de esta longitud el mensaje se marca como "muy largo" en
            // el listado, para poder reaccionar antes de imprimir el lote.
            $table->unsignedSmallInteger('max_message_length')->default(600)->after('min_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            $table->dropColumn(['min_font_size', 'max_message_length']);
        });
    }
};
