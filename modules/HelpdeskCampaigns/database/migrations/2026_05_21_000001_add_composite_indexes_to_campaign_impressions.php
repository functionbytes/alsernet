<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_campaign_impressions', function (Blueprint $table) {
            // FrequencyCapService: COUNT/latest per campaign+visitor
            $table->index(['campaign_id', 'customer_id', 'viewed_at'], 'hci_campaign_customer_viewed');
            $table->index(['campaign_id', 'customer_session_id', 'viewed_at'], 'hci_campaign_session_viewed');

            // statisticsTimeline: GROUP BY DATE(viewed_at) per campaign
            $table->index(['campaign_id', 'viewed_at'], 'hci_campaign_viewed');

            // Variant analytics
            $table->index(['campaign_id', 'variant_id'], 'hci_campaign_variant');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_campaign_impressions', function (Blueprint $table) {
            $table->dropIndex('hci_campaign_customer_viewed');
            $table->dropIndex('hci_campaign_session_viewed');
            $table->dropIndex('hci_campaign_viewed');
            $table->dropIndex('hci_campaign_variant');
        });
    }
};
