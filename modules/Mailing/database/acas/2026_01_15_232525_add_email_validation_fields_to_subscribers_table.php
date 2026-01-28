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
        Schema::table('subscribers', function (Blueprint $table) {
            // Add status field (replaces is_subscribed boolean)
            $table->string('status')->default('pending')->after('name');

            // Add Mailrelay integration fields
            $table->unsignedBigInteger('mailrelay_id')->nullable()->unique()->after('status');

            // Add metadata and custom fields (JSON columns)
            $table->json('metadata')->nullable()->after('mailrelay_id');
            $table->json('custom_fields')->nullable()->after('metadata');

            // Add timestamp fields for tracking
            $table->timestamp('subscribed_at')->nullable()->after('custom_fields');
            $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
            $table->timestamp('last_synced_at')->nullable()->after('unsubscribed_at');
            $table->timestamp('validated_at')->nullable()->after('last_synced_at');

            // Add indexes for better query performance
            $table->index('status');
            $table->index('mailrelay_id');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['mailrelay_id']);
            $table->dropIndex(['validated_at']);

            // Drop columns
            $table->dropColumn([
                'status',
                'mailrelay_id',
                'metadata',
                'custom_fields',
                'subscribed_at',
                'unsubscribed_at',
                'last_synced_at',
                'validated_at',
            ]);
        });
    }
};
