<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_products', function (Blueprint $table) {
            $table->id('id_return_product');
            $table->foreignId('id_return_request')->constrained('return_requests')->cascadeOnDelete();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('product_quantity')->default(0);
            $table->decimal('product_price', 10, 2)->nullable();
            $table->integer('id_catalog')->nullable();
            $table->string('erp_product_id')->nullable();
            $table->timestamps();
            $table->index('id_return_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_products');
    }
};
