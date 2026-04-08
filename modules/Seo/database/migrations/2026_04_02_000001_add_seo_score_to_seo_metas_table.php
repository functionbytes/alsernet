<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->tinyInteger('seo_score')->unsigned()->nullable()->after('robots');
            $table->char('seo_grade', 1)->nullable()->after('seo_score');
            $table->timestamp('seo_audited_at')->nullable()->after('seo_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropColumn(['seo_score', 'seo_grade', 'seo_audited_at']);
        });
    }
};
