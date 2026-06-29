<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id')->constrained('ecommerce_product_options')->cascadeOnDelete();
            $table->string('option_value');
            $table->decimal('affect_price', 15, 2)->default(0);
            $table->string('affect_type', 20)->default('plus');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_option_values');
    }
};
