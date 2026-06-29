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
        $tablesToRename = [
            'subscribers' => 'mails_subscribers',
            'campaigns' => 'mails_campaigns',
            'lists' => 'mails_lists',
            'import_jobs' => 'mails_import_jobs',
            'campaign_analytics' => 'mails_campaign_analytics',
            'email_validations' => 'mails_email_validations',
            'mailrelay_groups' => 'mails_groups',
            'mailrelay_group_subscriber' => 'mails_group_subscriber',
            'email_templates' => 'mails_email_templates',
        ];

        foreach ($tablesToRename as $oldTable => $newTable) {
            if (Schema::hasTable($oldTable) && ! Schema::hasTable($newTable)) {
                Schema::rename($oldTable, $newTable);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesToRevert = [
            'mails_subscribers' => 'subscribers',
            'mails_campaigns' => 'campaigns',
            'mails_lists' => 'lists',
            'mails_import_jobs' => 'import_jobs',
            'mails_campaign_analytics' => 'campaign_analytics',
            'mails_email_validations' => 'email_validations',
            'mails_groups' => 'mailrelay_groups',
            'mails_group_subscriber' => 'mailrelay_group_subscriber',
            'mails_email_templates' => 'email_templates',
        ];

        foreach ($tablesToRevert as $newTable => $oldTable) {
            if (Schema::hasTable($newTable) && ! Schema::hasTable($oldTable)) {
                Schema::rename($newTable, $oldTable);
            }
        }
    }
};
