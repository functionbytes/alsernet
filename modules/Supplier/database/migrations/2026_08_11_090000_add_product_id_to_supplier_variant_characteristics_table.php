<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            // Permite asignar un valor a un producto SIN variantes, tratándolo
            // como su propio artículo (erp_article_id = erp_id del producto).
            // product_attribute_id se queda null en ese caso; product_id
            // desambigua entre distintos productos que también carezcan de
            // variantes (si no, "characteristic_id + null" colisionaría entre
            // productos distintos en el updateOrCreate).
            $table->foreignId('product_id')->nullable()->after('erp_id')
                ->constrained('supplier_products')->nullOnDelete();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
