<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->index('referrer_domain', 'page_views_referrer_domain_index');
        });

        Schema::table('page_performance_metrics', function (Blueprint $table) {
            $table->index('performance_score', 'page_performance_score_index');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('page_views_referrer_domain_index');
        });

        Schema::table('page_performance_metrics', function (Blueprint $table) {
            $table->dropIndex('page_performance_score_index');
        });
    }
};
