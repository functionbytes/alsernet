<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_request_sends', function (Blueprint $table) {
            // campaign_id: used in all campaign->sends() relationship queries
            $table->index('campaign_id');

            // status: queried frequently (sent(), where('status', 'failed'), where('status', 'pending'))
            $table->index('status');

            // opened_at: used in whereNotNull('opened_at') for open-rate stats
            $table->index('opened_at');

            // Composite: most common stats pattern — filter by campaign then status
            $table->index(['campaign_id', 'status'], 'review_request_sends_campaign_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('review_request_sends', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['opened_at']);
            $table->dropIndex('review_request_sends_campaign_status_index');
        });
    }
};
