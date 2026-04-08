<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            // Replace any existing unique index on seoable_id + seoable_type
            // with one that includes locale so we can have one record per model per locale.
            // MySQL allows multiple NULLs in a UNIQUE index, so locale = NULL means "global default".
            $table->unique(['seoable_id', 'seoable_type', 'locale'], 'seo_metas_seoable_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropUnique('seo_metas_seoable_locale_unique');
        });
    }
};
