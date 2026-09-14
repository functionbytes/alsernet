<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            $table->string('supplier_code', 100)
                ->nullable()
                ->after('ean')
                ->comment('artiprov.codigo - referencia del proveedor');

            $table->string('supplier_code2', 100)
                ->nullable()
                ->after('supplier_code')
                ->comment('artiprov.codigo2 - referencia alternativa del proveedor');

            $table->string('ean13', 32)
                ->nullable()
                ->after('supplier_code2')
                ->comment('artiprov.ean13 - código EAN-13 del proveedor');

            $table->string('upc', 32)
                ->nullable()
                ->after('ean13')
                ->comment('artiprov.upc - código UPC del proveedor');

            $table->index('supplier_code');
            $table->index('ean13');
            $table->index('upc');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            $table->dropIndex(['supplier_code']);
            $table->dropIndex(['ean13']);
            $table->dropIndex(['upc']);
            $table->dropColumn(['supplier_code', 'supplier_code2', 'ean13', 'upc']);
        });
    }
};
