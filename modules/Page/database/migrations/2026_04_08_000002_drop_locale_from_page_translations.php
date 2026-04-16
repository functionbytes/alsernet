<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('page_translations', 'locale')) {
            // Drop indexes that reference 'locale' column before dropping the column
            $indexes = collect(Schema::getIndexes('page_translations'))->pluck('name');
            Schema::table('page_translations', function (Blueprint $table) use ($indexes) {
                if ($indexes->contains('page_translations_page_id_locale_unique')) {
                    $table->dropUnique('page_translations_page_id_locale_unique');
                }
                if ($indexes->contains('page_translations_locale_slug_index')) {
                    $table->dropIndex('page_translations_locale_slug_index');
                }
                if ($indexes->contains('page_translations_locale_status_published_at_index')) {
                    $table->dropIndex('page_translations_locale_status_published_at_index');
                }
            });
            DB::statement('ALTER TABLE page_translations DROP COLUMN `locale`');
        }

        $indexes = collect(Schema::getIndexes('page_translations'))->pluck('name');
        Schema::table('page_translations', function (Blueprint $table) use ($indexes) {
            if (Schema::hasColumn('page_translations', 'locale_id') &&
                ! $indexes->contains('page_translations_page_id_locale_id_unique')) {
                $table->unique(['page_id', 'locale_id']);
            }
            if (! $indexes->contains('page_translations_locale_id_slug_index')) {
                $table->index(['locale_id', 'slug']);
            }
            if (! $indexes->contains('page_translations_locale_id_status_published_at_index')) {
                $table->index(['locale_id', 'status', 'published_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'locale_id']);
            $table->dropIndex(['locale_id', 'slug']);
            $table->dropIndex(['locale_id', 'status', 'published_at']);
            $table->string('locale', 10)->nullable()->after('page_id');
        });
    }
};
