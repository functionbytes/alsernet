<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('author_name', 255);
            $table->string('author_email', 255)->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('answered_by', 255)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_questions');
    }
};
