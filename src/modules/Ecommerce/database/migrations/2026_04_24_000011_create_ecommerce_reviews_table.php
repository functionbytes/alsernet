<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->tinyInteger('star')->unsigned();
            $table->text('comment')->nullable();
            $table->string('status', 60)->default('pending');
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_reviews');
    }
};
