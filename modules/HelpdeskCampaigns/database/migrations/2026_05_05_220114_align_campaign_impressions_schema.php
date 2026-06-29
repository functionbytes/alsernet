<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('helpdesk');

        $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'customer_session_id')) {
                $table->unsignedBigInteger('customer_session_id')->nullable()->after('customer_id');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'page_url')) {
                $table->string('page_url', 2000)->nullable()->after('customer_session_id');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'device_type')) {
                $table->string('device_type', 32)->nullable()->after('page_url');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'browser')) {
                $table->string('browser', 100)->nullable()->after('device_type');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('browser');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'country')) {
                $table->string('country', 8)->nullable()->after('ip_address');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('country');
            }
            if (! $schema->hasColumn('helpdesk_campaign_impressions', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('viewed_at');
                $table->index('clicked_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('helpdesk');

        $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) use ($schema) {
            foreach (['clicked_at', 'viewed_at', 'country', 'ip_address', 'browser', 'device_type', 'page_url', 'customer_session_id'] as $col) {
                if ($schema->hasColumn('helpdesk_campaign_impressions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
