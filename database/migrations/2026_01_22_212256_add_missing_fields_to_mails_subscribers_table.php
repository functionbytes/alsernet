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
            if (! Schema::hasColumn('mails_subscribers', 'mailrelay_id')) {
                $table->string('mailrelay_id')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'subscribed_at')) {
                $table->timestamp('subscribed_at')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'unsubscribed_at')) {
                $table->timestamp('unsubscribed_at')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
            if (! Schema::hasColumn('mails_subscribers', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails_subscribers', function (Blueprint $table) {
            $table->dropColumn([
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
