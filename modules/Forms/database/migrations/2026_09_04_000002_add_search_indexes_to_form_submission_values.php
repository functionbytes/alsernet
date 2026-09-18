<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de búsqueda sobre form_submission_values.
 *
 * En el origen los añadía una migración global del proyecto
 * (add_global_performance_indexes), que además tocaba tablas de otros módulos
 * que aquí no existen; se extrae la parte de Forms para que el módulo traiga
 * sus propios índices.
 *
 *  - field_key:  FormSubmissionController::bulkAnonymize filtra WHERE field_key LIKE ?
 *  - field_type: usado por los filtros del inbox
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submission_values', function (Blueprint $table) {
            if (! $this->hasIndex('form_submission_values', 'fsv_field_key_index')) {
                $table->index('field_key', 'fsv_field_key_index');
            }

            if (! $this->hasIndex('form_submission_values', 'fsv_field_type_index')) {
                $table->index('field_type', 'fsv_field_type_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submission_values', function (Blueprint $table) {
            $table->dropIndexIfExists('fsv_field_key_index');
            $table->dropIndexIfExists('fsv_field_type_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $prefixed = DB::getTablePrefix().$table;

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $prefixed)
            ->where('index_name', $index)
            ->exists();
    }
};
