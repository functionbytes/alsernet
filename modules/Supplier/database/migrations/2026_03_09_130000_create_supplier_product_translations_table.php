<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('supplier_products')->onDelete('cascade');
            $table->foreignId('lang_id')->constrained('langs')->onDelete('cascade');
            $table->text('description');
            $table->boolean('generated_by_ai')->default(false)->index();
            $table->text('prompt_used')->nullable();
            $table->string('ai_model', 100)->nullable(); // gpt-4, claude-3-sonnet, etc.
            $table->timestamps();

            // One translation per language per product
            $table->unique(['product_id', 'lang_id']);

            // Indexes
            $table->index('lang_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_product_translations');
    }
};
