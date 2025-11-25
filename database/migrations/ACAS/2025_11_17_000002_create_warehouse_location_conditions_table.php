<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para las condiciones de ubicaciones (estados/condiciones)
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_location_conditions')) {
            Schema::create('warehouse_location_conditions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->string('title', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->boolean('available')->default(true);
                $table->string('color')->default('#999999')->after('slug')->comment('Hex color for UI display');
                $table->string('badge_class')->default('badge-secondary')->after('color')->comment('Bootstrap badge class');
                $table->timestamps();

                // Indexes
                $table->index('slug');
                $table->index('available');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_location_conditions');
    }
};
