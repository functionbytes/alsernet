<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->string('ai_model', 60)->nullable()->after('enable_web_search')
                ->comment('Modelo IA específico para este prompt. Null = usar el modelo global por defecto.');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->dropColumn('ai_model');
        });
    }
};
