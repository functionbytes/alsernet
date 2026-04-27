<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table): void {
            $table->foreignId('label_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('ecommerce_product_labels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table): void {
            $table->dropForeign(['label_id']);
            $table->dropColumn('label_id');
        });
    }
};
