<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal de almacenes/sedes
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('available')->default(true);
                $table->softDeletes();
                $table->timestamps();
                $table->index('available');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
