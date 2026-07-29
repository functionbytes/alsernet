<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_ai_content_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('badge_class')->default('bg-secondary');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Insertar estados por defecto
        Schema::table('supplier_ai_content_statuses', function (Blueprint $table) {
            // Los datos se insertan en el seeder
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_content_statuses');
    }
};
