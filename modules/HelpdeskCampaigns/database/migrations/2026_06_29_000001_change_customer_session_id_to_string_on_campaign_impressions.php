<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('helpdesk');

        if (! $schema->hasColumn('helpdesk_campaign_impressions', 'customer_session_id')) {
            return;
        }

        $hasIndex = $schema->hasIndex('helpdesk_campaign_impressions', 'hci_campaign_session_viewed');

        if ($hasIndex) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->dropIndex('hci_campaign_session_viewed');
            });
        }

        $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
            $table->string('customer_session_id', 255)->nullable()->change();
        });

        if ($hasIndex) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->index(['campaign_id', 'customer_session_id', 'viewed_at'], 'hci_campaign_session_viewed');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('helpdesk');

        if (! $schema->hasColumn('helpdesk_campaign_impressions', 'customer_session_id')) {
            return;
        }

        $hasIndex = $schema->hasIndex('helpdesk_campaign_impressions', 'hci_campaign_session_viewed');

        if ($hasIndex) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->dropIndex('hci_campaign_session_viewed');
            });
        }

        $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_session_id')->nullable()->change();
        });

        if ($hasIndex) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->index(['campaign_id', 'customer_session_id', 'viewed_at'], 'hci_campaign_session_viewed');
            });
        }
    }
};
