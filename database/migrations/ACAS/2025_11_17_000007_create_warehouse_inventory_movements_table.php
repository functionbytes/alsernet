<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de auditoría de movimientos de inventario
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_inventory_movements')) {
            Schema::create('warehouse_inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->foreignId('slot_id')
                    ->constrained('warehouse_inventory_slots')
                    ->onDelete('cascade');
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->onDelete('set null');
                $table->enum('movement_type', ['add', 'subtract', 'clear', 'move', 'count'])->default('add');
                $table->integer('from_quantity')->nullable();
                $table->integer('to_quantity')->nullable();
                $table->integer('quantity_delta')->default(0);
                $table->decimal('from_weight', 10, 2)->nullable();
                $table->decimal('to_weight', 10, 2)->nullable();
                $table->decimal('weight_delta', 10, 2)->default(0);
                $table->text('reason')->nullable();
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->constrained('warehouses')
                    ->onDelete('set null');
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
                $table->dateTime('recorded_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('slot_id');
                $table->index('product_id');
                $table->index('movement_type');
                $table->index('user_id');
                $table->index('warehouse_id');
                $table->index('recorded_at');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_movements');
    }
};
