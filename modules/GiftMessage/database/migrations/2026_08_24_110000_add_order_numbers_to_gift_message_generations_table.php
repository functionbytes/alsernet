<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            // Array de numeros de pedido (npedidocli, o el identificador que haya
            // disponible) incluidos en el PDF. Nullable: las generaciones previas
            // a este cambio no lo tienen y solo mostraran el contador.
            $table->json('order_numbers')->nullable()->after('rows_count');
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_generations', function (Blueprint $table) {
            $table->dropColumn('order_numbers');
        });
    }
};
