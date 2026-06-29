<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_specification_tables', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('table_id');

            $table->primary(['product_id', 'table_id']);

            $table->foreign('product_id')
                ->references('id')
                ->on('ecommerce_products')
                ->cascadeOnDelete();

            $table->foreign('table_id')
                ->references('id')
                ->on('ecommerce_specification_tables')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_specification_tables');
    }
};
