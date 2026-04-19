<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (
                Schema::hasColumn('seo_metas', 'locale') &&
                ! Schema::hasIndex('seo_metas', 'seo_metas_locale_index')
            ) {
                $table->index('locale');
            }
        });

        Schema::table('seo_redirects', function (Blueprint $table) {
            if (
                Schema::hasColumn('seo_redirects', 'is_wildcard') &&
                ! Schema::hasIndex('seo_redirects', 'seo_redirects_is_wildcard_index')
            ) {
                $table->index('is_wildcard');
            }
            if (
                Schema::hasColumn('seo_redirects', 'is_regex') &&
                ! Schema::hasIndex('seo_redirects', 'seo_redirects_is_regex_index')
            ) {
                $table->index('is_regex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (Schema::hasIndex('seo_metas', 'seo_metas_locale_index')) {
                $table->dropIndex('seo_metas_locale_index');
            }
        });

        Schema::table('seo_redirects', function (Blueprint $table) {
            if (Schema::hasIndex('seo_redirects', 'seo_redirects_is_wildcard_index')) {
                $table->dropIndex('seo_redirects_is_wildcard_index');
            }
            if (Schema::hasIndex('seo_redirects', 'seo_redirects_is_regex_index')) {
                $table->dropIndex('seo_redirects_is_regex_index');
            }
        });
    }
};
