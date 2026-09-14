<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('supplier_categories')->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('Categoria principal del proveedor');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'category_id']);
            $table->index('supplier_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_supplier_categories');
    }
};
