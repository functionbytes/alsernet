<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de estilos de estanterías (Stand Styles)
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_location_styles')) {
            Schema::create('warehouse_location_styles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid')->unique();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->integer('width')->nullable();
                $table->integer('height')->nullable();
                $table->json('faces')->default(json_encode(['front', 'back', 'left', 'right']));
                $table->json('types')->default(json_encode(['row', 'island', 'wall']));
                $table->integer('default_levels')->default(3);
                $table->boolean('available')->default(true);
                $table->timestamps();

                // Indexes
                $table->index('code');
                $table->index('name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_location_styles');
    }
};
