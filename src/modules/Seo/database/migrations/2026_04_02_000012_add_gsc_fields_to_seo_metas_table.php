<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->unsignedInteger('gsc_clicks')->nullable()->after('seo_audited_at');
            $table->unsignedInteger('gsc_impressions')->nullable()->after('gsc_clicks');
            $table->decimal('gsc_position', 5, 1)->nullable()->after('gsc_impressions');
            $table->timestamp('gsc_updated_at')->nullable()->after('gsc_position');
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropColumn(['gsc_clicks', 'gsc_impressions', 'gsc_position', 'gsc_updated_at']);
        });
    }
};
