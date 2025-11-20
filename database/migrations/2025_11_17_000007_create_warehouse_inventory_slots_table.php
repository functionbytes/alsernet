<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de posiciones de inventario (Slots)
     * Representa posiciones concretas dentro de una estantería
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_inventory_slots')) {
            Schema::create('warehouse_inventory_slots', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->foreignId('section_id')->nullable()->constrained('warehouse_location_sections')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->integer('quantity')->default(0);
                $table->integer('kardex')->default(0);
                $table->boolean('is_occupied')->default(false);
                $table->dateTime('last_movement')->nullable();
                $table->foreignId('last_section_id')->nullable()->constrained('warehouse_location_sections')->onDelete('set null');
                $table->timestamps();

                // Indexes
                $table->index('section_id');
                $table->index('product_id');
                $table->index('last_section_id');
                $table->unique(['section_id', 'product_id', 'last_section_id'], 'unique_slot_per_section');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_slots');
    }
};
