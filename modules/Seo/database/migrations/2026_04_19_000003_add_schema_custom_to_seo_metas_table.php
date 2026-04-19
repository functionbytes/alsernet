<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_metas', 'schema_custom')) {
                $table->json('schema_custom')->nullable()->after('robots');
            }
            if (! Schema::hasColumn('seo_metas', 'schema_type')) {
                $table->string('schema_type', 50)->nullable()->after('schema_custom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (Schema::hasColumn('seo_metas', 'schema_type')) {
                $table->dropColumn('schema_type');
            }
            if (Schema::hasColumn('seo_metas', 'schema_custom')) {
                $table->dropColumn('schema_custom');
            }
        });
    }
};
