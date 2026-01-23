<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename import_jobs -> mails_import_jobs
        if (Schema::hasTable('import_jobs') && ! Schema::hasTable('mails_import_jobs')) {
            Schema::rename('import_jobs', 'mails_import_jobs');
        }

        // Rename campaigns -> mails_campaigns
        if (Schema::hasTable('campaigns') && ! Schema::hasTable('mails_campaigns')) {
            Schema::rename('campaigns', 'mails_campaigns');
        }

        // Rename campaign_analytics -> mails_campaign_analytics
        if (Schema::hasTable('campaign_analytics') && ! Schema::hasTable('mails_campaign_analytics')) {
            Schema::rename('campaign_analytics', 'mails_campaign_analytics');
        }

        // Rename email_validations -> mails_email_validations
        if (Schema::hasTable('email_validations') && ! Schema::hasTable('mails_email_validations')) {
            Schema::rename('email_validations', 'mails_email_validations');
        }

        // Rename mailrelay_groups -> mails_groups
        if (Schema::hasTable('mailrelay_groups') && ! Schema::hasTable('mails_groups')) {
            Schema::rename('mailrelay_groups', 'mails_groups');
        }

        // Rename mailrelay_group_subscriber -> mails_group_subscriber
        if (Schema::hasTable('mailrelay_group_subscriber') && ! Schema::hasTable('mails_group_subscriber')) {
            Schema::rename('mailrelay_group_subscriber', 'mails_group_subscriber');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: mails_import_jobs -> import_jobs
        if (Schema::hasTable('mails_import_jobs') && ! Schema::hasTable('import_jobs')) {
            Schema::rename('mails_import_jobs', 'import_jobs');
        }

        // Revert: mails_campaigns -> campaigns
        if (Schema::hasTable('mails_campaigns') && ! Schema::hasTable('campaigns')) {
            Schema::rename('mails_campaigns', 'campaigns');
        }

        // Revert: mails_campaign_analytics -> campaign_analytics
        if (Schema::hasTable('mails_campaign_analytics') && ! Schema::hasTable('campaign_analytics')) {
            Schema::rename('mails_campaign_analytics', 'campaign_analytics');
        }

        // Revert: mails_email_validations -> email_validations
        if (Schema::hasTable('mails_email_validations') && ! Schema::hasTable('email_validations')) {
            Schema::rename('mails_email_validations', 'email_validations');
        }

        // Revert: mails_groups -> mailrelay_groups
        if (Schema::hasTable('mails_groups') && ! Schema::hasTable('mailrelay_groups')) {
            Schema::rename('mails_groups', 'mailrelay_groups');
        }

        // Revert: mails_group_subscriber -> mailrelay_group_subscriber
        if (Schema::hasTable('mails_group_subscriber') && ! Schema::hasTable('mailrelay_group_subscriber')) {
            Schema::rename('mails_group_subscriber', 'mailrelay_group_subscriber');
        }
    }
};
