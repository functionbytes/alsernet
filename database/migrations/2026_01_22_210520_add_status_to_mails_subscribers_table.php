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
        Schema::table('mails_subscribers', function (Blueprint $table) {
            $table->enum('status', ['active', 'unsubscribed', 'bounced', 'pending', 'banned'])->default('pending')->after('name');
            $table->string('mailrelay_id')->nullable()->after('status');
            $table->json('metadata')->nullable()->after('mailrelay_id');
            $table->json('custom_fields')->nullable()->after('metadata');
            $table->timestamp('subscribed_at')->nullable()->after('custom_fields');
            $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
            $table->timestamp('validated_at')->nullable()->after('unsubscribed_at');
            $table->timestamp('last_synced_at')->nullable()->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails_subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'mailrelay_id',
                'metadata',
                'custom_fields',
                'subscribed_at',
                'unsubscribed_at',
                'validated_at',
                'last_synced_at',
            ]);
        });
    }
};
