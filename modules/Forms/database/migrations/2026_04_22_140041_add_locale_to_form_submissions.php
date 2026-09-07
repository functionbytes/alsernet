<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FormSubmissionService::process() escribe siempre `locale` (app()->getLocale()),
 * así que sin esta columna todo envío público revienta con un 1054.
 *
 * En el proyecto de origen esta migración vivía en el `database/migrations`
 * raíz, no dentro del módulo; se trae aquí para que Forms sea autocontenido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('form_submissions', 'locale')) {
            return;
        }

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('source_page_id');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
