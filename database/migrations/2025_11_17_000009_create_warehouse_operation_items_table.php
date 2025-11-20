<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para rastrear items específicos dentro de una operación
     */
    public function up(): void
    {
        Schema::create('warehouse_operation_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Operation & Slot relationship
            $table->foreignId('operation_id')
                ->constrained('warehouse_inventory_operations')
                ->cascadeOnDelete();

            $table->foreignId('slot_id')
                ->constrained('warehouse_inventory_slots')
                ->cascadeOnDelete();

            // Expected vs Actual
            $table->integer('expected_quantity')->default(0)->comment('Kardex quantity');
            $table->integer('actual_quantity')->default(0)->comment('Scanned/Counted quantity');
            $table->integer('difference')->default(0)->comment('Difference: actual - expected');

            // Status
            $table->enum('status', ['pending', 'validated', 'discrepancy', 'missing'])
                ->default('pending')
                ->comment('pending=not yet scanned, validated=match, discrepancy=mismatch, missing=in kardex but not found');

            // Validation
            $table->boolean('is_validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('operation_id');
            $table->index('slot_id');
            $table->index('status');
            $table->index('is_validated');
            $table->unique(['operation_id', 'slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_operation_items');
    }
};
