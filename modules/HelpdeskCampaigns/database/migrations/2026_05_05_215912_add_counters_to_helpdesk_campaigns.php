<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_campaigns', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_campaigns', 'impressions_count')) {
                $table->unsignedBigInteger('impressions_count')->default(0)->after('status');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_campaigns', 'clicks_count')) {
                $table->unsignedBigInteger('clicks_count')->default(0)->after('impressions_count');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_campaigns', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_campaigns', 'clicks_count')) {
                $table->dropColumn('clicks_count');
            }

            if (Schema::connection('helpdesk')->hasColumn('helpdesk_campaigns', 'impressions_count')) {
                $table->dropColumn('impressions_count');
            }
        });
    }
};
