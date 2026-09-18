<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refleja aquí el interruptor que vive en la tienda: si este formulario
 * sustituye o no al .tpl de código.
 *
 * La fuente de verdad es PrestaShop (es quien decide qué renderiza); esta
 * columna es una copia para poder pintar el estado en el editor sin ir a
 * preguntárselo a la tienda en cada carga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_prestashop_publications', function (Blueprint $table) {
            $table->boolean('overrides_legacy')->default(false)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('form_prestashop_publications', function (Blueprint $table) {
            $table->dropColumn('overrides_legacy');
        });
    }
};
