<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_product_attributes', 'ean')) {
                $table->dropIndex(['ean']);
                $table->dropColumn('ean');
            }
            if (Schema::hasColumn('supplier_product_attributes', 'supplier_code')) {
                $table->dropIndex(['supplier_code']);
                $table->dropColumn('supplier_code');
            }
            if (Schema::hasColumn('supplier_product_attributes', 'supplier_code2')) {
                $table->renameColumn('supplier_code2', 'code_secundary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_product_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_product_attributes', 'code_secundary')) {
                $table->renameColumn('code_secundary', 'supplier_code2');
            }
            $table->string('supplier_code', 100)->nullable()->after('reference');
            $table->string('ean', 32)->nullable()->after('reference');
            $table->index('supplier_code');
            $table->index('ean');
        });
    }
};
