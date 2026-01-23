<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns exist before adding
        if (! Schema::hasColumn('mails_subscribers', 'mailrelay_id')) {
            Schema::table('mails_subscribers', function (Blueprint $table) {
                $table->string('mailrelay_id')->nullable()->unique()->after('name');
                $table->json('metadata')->nullable()->after('mailrelay_id');
                $table->json('custom_fields')->nullable()->after('metadata');
                $table->timestamp('subscribed_at')->nullable()->after('custom_fields');
                $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
                $table->timestamp('validated_at')->nullable()->after('unsubscribed_at');
                $table->timestamp('last_synced_at')->nullable()->after('validated_at');
            });
        }

        // Update status values from 'subscribed' to 'active' if needed
        DB::table('mails_subscribers')->where('status', 'subscribed')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails_subscribers', function (Blueprint $table) {
            $columns = [
                'mailrelay_id',
                'metadata',
                'custom_fields',
                'subscribed_at',
                'unsubscribed_at',
                'validated_at',
                'last_synced_at',
            ];

            // Only drop columns that exist
            foreach ($columns as $column) {
                if (Schema::hasColumn('mails_subscribers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
