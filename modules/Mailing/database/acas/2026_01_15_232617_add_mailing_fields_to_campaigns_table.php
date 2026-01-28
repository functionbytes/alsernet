<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add Mailrelay campaign ID (nullable, unique)
            $table->unsignedBigInteger('mailrelay_campaign_id')->nullable()->unique()->after('id');

            // Add sender ID for Mailrelay
            $table->unsignedBigInteger('sender_id')->nullable()->after('text_content');

            // Add recipient count
            $table->integer('recipient_count')->default(0)->after('status');

            // Add metadata JSON field
            $table->json('metadata')->nullable()->after('recipient_count');

            // Add scheduled_at timestamp
            $table->timestamp('scheduled_at')->nullable()->after('metadata');

            // Add sent_at timestamp
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');

            // Add soft deletes
            $table->softDeletes();

            // Add indexes for performance
            $table->index('mailrelay_campaign_id');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['mailrelay_campaign_id']);
            $table->dropIndex(['scheduled_at']);

            // Drop columns
            $table->dropColumn([
                'mailrelay_campaign_id',
                'sender_id',
                'recipient_count',
                'metadata',
                'scheduled_at',
                'sent_at',
                'deleted_at',
            ]);
        });
    }
};
