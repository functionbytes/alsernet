<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('ecommerce_product_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 60)->default('published');
            $table->integer('order')->unsigned()->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index('status');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_categories');
    }
};
