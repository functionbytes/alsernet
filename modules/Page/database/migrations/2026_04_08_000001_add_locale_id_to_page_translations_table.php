<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->unsignedBigInteger('locale_id')->nullable()->after('locale');
            $table->foreign('locale_id')->references('id')->on('locales')->nullOnDelete();
            $table->index('locale_id');
        });

        if (Schema::hasTable('locales')) {
            DB::statement('
                UPDATE page_translations pt
                JOIN locales l ON l.code = pt.locale
                SET pt.locale_id = l.id
            ');
        }
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropForeign(['locale_id']);
            $table->dropIndex(['locale_id']);
            $table->dropColumn('locale_id');
        });
    }
};
