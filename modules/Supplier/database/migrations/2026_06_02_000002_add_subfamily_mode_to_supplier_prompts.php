<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda explícitamente el modo de selección de subfamilia elegido por el usuario:
 *   'single' → cascada jerárquica, una sola subfamilia
 *   'multi'  → búsqueda directa, múltiples subfamilias
 *
 * Se infiere del estado actual para los registros existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->string('subfamily_mode', 10)->default('single')->after('subfamily_ids')
                ->comment('Modo de selección: single (cascada) o multi (multiselect)');
        });

        // Infiere el modo de los registros existentes
        DB::statement("
            UPDATE supplier_prompts
            SET subfamily_mode = CASE
                WHEN JSON_LENGTH(subfamily_ids) > 1 THEN 'multi'
                ELSE 'single'
            END
            WHERE subfamily_ids IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->dropColumn('subfamily_mode');
        });
    }
};
