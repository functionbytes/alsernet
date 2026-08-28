<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            // Alineacion del texto dentro de su caja. La vertical importa al
            // imprimir en lote: con "arriba", mensajes de distinta longitud
            // arrancan todos a la misma altura en vez de flotar cada uno a la suya.
            foreach (['env', 'card'] as $piece) {
                foreach (['t1', 't2'] as $slot) {
                    $table->string("{$piece}_{$slot}_align", 10)->default('center');
                    $table->string("{$piece}_{$slot}_valign", 10)->default('middle');
                }
            }

            // Aire entre parrafos, en fracciones del tamano de letra.
            $table->decimal('paragraph_spacing', 3, 2)->default(0.35);
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            $columns = ['paragraph_spacing'];

            foreach (['env', 'card'] as $piece) {
                foreach (['t1', 't2'] as $slot) {
                    $columns[] = "{$piece}_{$slot}_align";
                    $columns[] = "{$piece}_{$slot}_valign";
                }
            }

            $table->dropColumn($columns);
        });
    }
};
