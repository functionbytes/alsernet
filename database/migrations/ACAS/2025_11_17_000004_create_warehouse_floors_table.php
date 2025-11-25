<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de pisos en el almacén
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_floors')) {
            Schema::create('warehouse_floors', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->integer('level')->default(1);
                $table->boolean('available')->default(true);
                $table->timestamps();

                // Indexes
                $table->index('warehouse_id');
                $table->index('level');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_floors');
    }
};
