<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            $table->string('ean', 32)
                ->nullable()
                ->after('reference')
                ->comment('articulo.ean_interno Oracle');

            $table->index('ean');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            $table->dropIndex(['ean']);
            $table->dropColumn('ean');
        });
    }
};
