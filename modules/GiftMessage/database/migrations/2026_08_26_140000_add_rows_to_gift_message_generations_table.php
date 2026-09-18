<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            // Filas tal como se imprimieron (mensaje regalo incluido), para poder
            // reimprimir un pedido suelto de un PDF con varios sin depender de que
            // el bridge siga devolviendo lo mismo. Nullable: las generaciones
            // anteriores a este cambio no lo tienen y caen a consultar el bridge.
            $table->json('rows')->nullable()->after('order_numbers');
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            $table->dropColumn('rows');
        });
    }
};
