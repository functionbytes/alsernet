<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite asignar múltiples subfamilias a un prompt.
 * - Añade subfamily_ids (JSON) con el array de IDs locales.
 * - Migra el valor existente de subfamily_id → subfamily_ids.
 * - Mantiene subfamily_id para compatibilidad (se usa como "primer valor" en lecturas legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->json('subfamily_ids')->nullable()->after('subfamily_id')
                ->comment('Array de IDs locales de subfamilia (multiselect)');
        });

        // Migrar valores existentes: si tiene subfamily_id → poblar subfamily_ids
        DB::statement("
            UPDATE supplier_prompts
            SET subfamily_ids = JSON_ARRAY(subfamily_id)
            WHERE subfamily_id IS NOT NULL AND subfamily_ids IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->dropColumn('subfamily_ids');
        });
    }
};
