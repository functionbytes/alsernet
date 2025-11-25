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
                $table->foreignId('location_id')
                    ->constrained('warehouse_locations')
                    ->onDelete('cascade');
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->onDelete('set null');
                $table->enum('face', ['left', 'right', 'front', 'back'])->default('front');
                $table->integer('level')->default(1);
                $table->integer('section')->default(1);
                $table->string('barcode', 100)->nullable();
                $table->integer('quantity')->default(0);
                $table->integer('max_quantity')->nullable();
                $table->decimal('weight_current', 10, 2)->default(0);
                $table->decimal('weight_max', 10, 2)->nullable();
                $table->boolean('is_occupied')->default(false);
                $table->dateTime('last_movement')->nullable();
                $table->foreignId('last_warehouse_id')
                    ->nullable()
                    ->constrained('warehouses')
                    ->onDelete('set null');
                $table->timestamps();

                // Indexes
                $table->index('location_id');
                $table->index('product_id');
                $table->index('face');
                $table->index('is_occupied');
                $table->index('last_warehouse_id');
                $table->index('barcode');
                $table->unique(['location_id', 'face', 'level', 'section']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_slots');
    }
};
