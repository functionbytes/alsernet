<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: This migration creates the product_blockades table for the Prestashop module.
     * This table was previously named 'document_product_blockades' and was managed by the Document module.
     * It is now owned and managed by the Prestashop module.
     */
    public function up(): void
    {
        Schema::create('product_blockades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable()->comment('Source ID');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_attribute_id')->nullable();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->timestamps();

            $table->index('product_id');
            $table->index('product_attribute_id');
            $table->index('document_type_id');
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_blockades');
    }
};
