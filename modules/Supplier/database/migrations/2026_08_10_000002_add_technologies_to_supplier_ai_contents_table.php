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
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            // Varios prompts (Nike, Adidas...) piden explícitamente un campo
            // "technologies" en el JSON de respuesta (ej. ["Air Max", "React"])
            // que hasta ahora se descartaba en silencio al no existir columna.
            $table->json('technologies')->nullable()->after('bullet_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropColumn('technologies');
        });
    }
};
