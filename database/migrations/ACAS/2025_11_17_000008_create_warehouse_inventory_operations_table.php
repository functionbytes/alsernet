<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de operaciones de inventario
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_inventory_operations')) {
            Schema::create('warehouse_inventory_operations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();

                $table->foreignId('warehouse_id')
                    ->constrained('warehouses')
                    ->onDelete('cascade');

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('restrict');

                $table->dateTime('started_at');
                $table->dateTime('closed_at')->nullable();

                $table->foreignId('closed_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');

                $table->text('description')->nullable();

                // Campos de estado y contadores (SIN ->after())
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                    ->default('pending')
                    ->comment('Operation status: pending, in_progress, completed, cancelled');

                $table->integer('total_items')
                    ->default(0)
                    ->comment('Total items expected in this operation');

                $table->integer('validated_items')
                    ->default(0)
                    ->comment('Items that match kardex = quantity');

                $table->integer('discrepancy_items')
                    ->default(0)
                    ->comment('Items with kardex != quantity');

                $table->timestamps();

                // Indexes
                $table->index('status');
                $table->index('warehouse_id');
                $table->index('user_id');
                $table->index('closed_by');
                $table->index('started_at');
                $table->index('closed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_operations');
    }
};
