<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cambia las FK de la jerarquía de categorías a CASCADE para que al eliminar
 * un deporte se eliminen sus familias, y al eliminar una familia sus subfamilias.
 *
 * sport → categorías (familias) → subfamilias
 *
 * Productos y atributos mantienen SET NULL (no se eliminan al borrar la categoría).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. supplier_categories.sport_id: SET NULL → CASCADE
        Schema::table('supplier_categories', function (Blueprint $table) {
            $table->dropForeign(['sport_id']);
            $table->foreign('sport_id')
                ->references('id')->on('supplier_sports')
                ->cascadeOnDelete();
        });

        // 2. supplier_subfamilies.category_id: nullOnDelete → CASCADE
        Schema::table('supplier_subfamilies', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')
                ->references('id')->on('supplier_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_categories', function (Blueprint $table) {
            $table->dropForeign(['sport_id']);
            $table->foreign('sport_id')
                ->references('id')->on('supplier_sports')
                ->nullOnDelete();
        });

        Schema::table('supplier_subfamilies', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')
                ->references('id')->on('supplier_categories')
                ->nullOnDelete();
        });
    }
};
