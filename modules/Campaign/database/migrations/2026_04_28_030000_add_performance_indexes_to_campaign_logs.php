<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_tracking_logs', function (Blueprint $table): void {
            $table->index(['campaign_id', 'status', 'created_at'], 'idx_campaign_tracking_logs_campaign_status_created');
            $table->index(['subscriber_id', 'created_at'], 'idx_campaign_tracking_logs_subscriber_created');
            $table->index(['message_id', 'status'], 'idx_campaign_tracking_logs_message_status');
        });

        Schema::table('campaign_open_logs', function (Blueprint $table): void {
            $table->index(['tracking_log_id', 'created_at'], 'idx_open_logs_tracking_created');
        });

        Schema::table('campaign_click_logs', function (Blueprint $table): void {
            $table->index(['tracking_log_id', 'created_at'], 'idx_click_logs_tracking_created');
            $table->index(['campaign_link_id', 'created_at'], 'idx_click_logs_link_created');
        });

        Schema::table('campaign_unsubscribe_logs', function (Blueprint $table): void {
            $table->index(['tracking_log_id', 'created_at'], 'idx_unsub_logs_tracking_created');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_tracking_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_campaign_tracking_logs_campaign_status_created');
            $table->dropIndex('idx_campaign_tracking_logs_subscriber_created');
            $table->dropIndex('idx_campaign_tracking_logs_message_status');
        });

        Schema::table('campaign_open_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_open_logs_tracking_created');
        });

        Schema::table('campaign_click_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_click_logs_tracking_created');
            $table->dropIndex('idx_click_logs_link_created');
        });

        Schema::table('campaign_unsubscribe_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_unsub_logs_tracking_created');
        });
    }
};
