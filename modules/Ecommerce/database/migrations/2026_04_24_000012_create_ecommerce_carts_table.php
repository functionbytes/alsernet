<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->integer('qty')->unsigned()->default(1);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_carts');
    }
};
