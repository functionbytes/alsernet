<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de ubicaciones/estanterías en el almacén (Stand/Location)
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->foreignId('floor_id')->constrained('warehouse_floors')->onDelete('cascade');
                $table->foreignId('style_id')->nullable()->constrained('warehouse_location_styles')->onDelete('set null');
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');

                $table->string('code', 50);
                $table->decimal('position_x', 8, 2)->nullable();
                $table->decimal('position_y', 8, 2)->nullable();
                $table->integer('total_levels')->default(3);
                $table->boolean('available')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('warehouse_id');
                $table->index('floor_id');
                $table->index('style_id');
                $table->index('code');
                $table->unique(['floor_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
